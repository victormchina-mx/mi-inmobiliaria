<?php

namespace App\Actions\Facturacion;

use App\Enums\EstadoFactura;
use App\Enums\FormaPago;
use App\Models\AplicacionPago;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\User;
use App\Services\Cfdi\Facturador;
use App\Support\GeneradorFolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registra un cobro y lo aplica a facturas. Lo que no se aplica queda como anticipo / saldo a favor.
 * Si se paga una factura PPD se emite el complemento de recepción de pagos (REP 2.0).
 */
class RegistrarPago
{
    public function __construct(private Facturador $facturador) {}

    /**
     * @param  array{cliente_id?: int|null, aseguradora_id?: int|null, orden_servicio_id?: int|null, fecha?: mixed, forma_pago: string|FormaPago, monto: float|string, referencia?: string|null, notas?: string|null}  $datos
     * @param  array<int, float|string|null>  $aplicar  factura_id => monto
     * @return array{0: Pago, 1: ?string} pago y, si falló el timbrado del REP, el motivo
     */
    public function __invoke(array $datos, array $aplicar = [], ?User $usuario = null): array
    {
        $monto = round((float) $datos['monto'], 2);
        if ($monto <= 0) {
            throw ValidationException::withMessages(['monto' => 'El monto debe ser mayor a cero.']);
        }

        $pago = DB::transaction(function () use ($datos, $monto, $aplicar, $usuario) {
            $pago = Pago::create([
                'folio' => GeneradorFolio::siguiente('REC'),
                'cliente_id' => $datos['cliente_id'] ?? null,
                'aseguradora_id' => $datos['aseguradora_id'] ?? null,
                'orden_servicio_id' => $datos['orden_servicio_id'] ?? null,
                'fecha' => $datos['fecha'] ?? now(),
                'forma_pago' => $datos['forma_pago'],
                'monto' => $monto,
                'por_aplicar' => $monto,
                'es_anticipo' => array_filter($aplicar, fn ($m) => (float) $m > 0) === [],
                'referencia' => $datos['referencia'] ?? null,
                'notas' => $datos['notas'] ?? null,
                'user_id' => $usuario?->id,
            ]);

            $this->aplicar($pago, $aplicar);

            return $pago;
        });

        return [$pago->refresh(), $this->complemento($pago)];
    }

    /**
     * Aplica (parte de) un pago ya registrado —por ejemplo un anticipo— a facturas.
     *
     * @param  array<int, float|string|null>  $aplicar
     * @return ?string motivo si el REP no se pudo timbrar
     */
    public function aplicarExistente(Pago $pago, array $aplicar): ?string
    {
        DB::transaction(fn () => $this->aplicar($pago, $aplicar));

        return $this->complemento($pago->refresh());
    }

    private function aplicar(Pago $pago, array $aplicar): void
    {
        $aplicar = array_filter(array_map(fn ($m) => round((float) $m, 2), $aplicar), fn ($m) => $m > 0);
        if ($aplicar === []) {
            return;
        }
        if ($pago->cancelado_at) {
            throw ValidationException::withMessages(['pago' => 'El pago está cancelado.']);
        }
        if (array_sum($aplicar) > $pago->por_aplicar + 0.009) {
            throw ValidationException::withMessages(['aplicar' => 'Lo aplicado ($'.number_format(array_sum($aplicar), 2).') supera lo disponible del pago ($'.number_format($pago->por_aplicar, 2).').']);
        }

        $facturas = Factura::whereIn('id', array_keys($aplicar))->lockForUpdate()->get()->keyBy('id');
        if ($facturas->pluck('receptor_rfc')->unique()->count() > 1) {
            throw ValidationException::withMessages(['aplicar' => 'Un pago solo puede aplicarse a facturas del mismo receptor (RFC).']);
        }

        foreach ($aplicar as $id => $m) {
            $f = $facturas[$id] ?? null;
            if (! $f || $f->tipo !== Factura::INGRESO || $f->estado !== EstadoFactura::Timbrada) {
                throw ValidationException::withMessages(['aplicar' => 'Solo se aplican pagos a facturas timbradas.']);
            }
            if ($m > $f->saldo + 0.009) {
                throw ValidationException::withMessages(['aplicar' => "El monto para {$f->folio_completo} supera su saldo ($".number_format($f->saldo, 2).').']);
            }

            AplicacionPago::create([
                'pago_id' => $pago->id, 'factura_id' => $f->id, 'monto' => $m, 'saldo_anterior' => $f->saldo,
                'parcialidad' => $f->aplicaciones()->count() + 1,
            ]);
            $f->forceFill(['saldo' => round($f->saldo - $m, 2)])->save();
        }

        $pago->forceFill(['por_aplicar' => round($pago->por_aplicar - array_sum($aplicar), 2)])->save();
    }

    /**
     * Emite (y timbra) el REP si el pago tiene aplicaciones a facturas PPD aún sin complemento.
     */
    private function complemento(Pago $pago): ?string
    {
        $pago->load('aplicaciones.factura');
        $ppd = $pago->aplicaciones->filter(fn ($a) => $a->factura->metodo_pago === 'PPD');
        if ($ppd->isEmpty()) {
            return null;
        }

        $rep = $pago->complemento()->first();
        if ($rep?->timbrada()) {
            // Ya tenía REP (aplicación posterior de un anticipo): se sustituye por uno nuevo con todo lo aplicado.
            $this->facturador->cancelar($rep, '02');
            $rep = null;
        }

        $doc = $ppd->first()->factura;
        $serie = config('tarzo.cfdi.serie_pago');
        $rep ??= Factura::create([
            ...$doc->only(['receptor_tipo', 'cliente_id', 'aseguradora_id', 'receptor_rfc', 'receptor_nombre', 'receptor_regimen', 'receptor_cp', 'email']),
            'tipo' => Factura::PAGO, 'serie' => $serie, 'folio' => GeneradorFolio::numero("CFDI-{$serie}"),
            'uso_cfdi' => 'CP01', 'moneda' => 'XXX', 'estado' => EstadoFactura::Borrador, 'pago_id' => $pago->id,
            'total' => $ppd->sum('monto'), 'user_id' => $pago->user_id,
            'notas' => "Pago {$pago->folio} · ".$ppd->map(fn ($a) => $a->factura->folio_completo)->implode(', '),
        ]);

        $pago->setRelation('complemento', $rep);

        try {
            $this->facturador->timbrar($rep);
        } catch (ValidationException $e) {
            return collect($e->errors())->flatten()->first();
        }

        return null;
    }
}
