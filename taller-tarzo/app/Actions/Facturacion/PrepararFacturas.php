<?php

namespace App\Actions\Facturacion;

use App\Enums\EstadoFactura;
use App\Enums\EstadoPartida;
use App\Enums\FormaPago;
use App\Enums\OperacionHyP;
use App\Enums\Paga;
use App\Enums\TipoPartida;
use App\Models\Factura;
use App\Models\OrdenServicio;
use App\Models\Partida;
use App\Models\User;
use App\Services\Cotizacion\Contexto;
use App\Services\Cotizacion\Cotizador;
use App\Support\GeneradorFolio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Arma los borradores de factura de una orden a partir de las partidas autorizadas.
 *
 *  - Particular / flotilla: una factura al cliente (a crédito PPD si la flotilla tiene días de crédito).
 *  - Seguro: a la aseguradora lo cubierto (PPD) con descuento = deducible + demérito + salvamento, para que
 *    el total sea el "monto aseguradora" del Cotizador; al cliente lo no cubierto + el deducible (PUE).
 *  - La cabina de pintura va con quien pague alguna pieza pintada (misma regla del Cotizador).
 */
class PrepararFacturas
{
    /**
     * @return Collection<int, Factura>
     */
    public function __invoke(OrdenServicio $orden, ?User $usuario = null): Collection
    {
        $orden->loadMissing(['cliente.datosFiscales', 'cliente.flotilla', 'aseguradora', 'siniestro']);
        $cotizacion = $orden->cotizacionActual()->first();
        $aprobadas = $cotizacion?->partidas()->where('estado', EstadoPartida::Aprobada->value)->orderBy('orden')->get() ?? collect();

        if ($aprobadas->isEmpty()) {
            throw ValidationException::withMessages(['orden' => 'La orden no tiene partidas autorizadas para facturar.']);
        }
        if ($orden->facturas()->where('tipo', Factura::INGRESO)->where('estado', '!=', EstadoFactura::Cancelada->value)->exists()) {
            throw ValidationException::withMessages(['orden' => 'La orden ya tiene facturas. Cancela la anterior o usa una nota de crédito.']);
        }

        $ctx = Contexto::paraCotizacion($cotizacion);
        $tasa = (float) ($cotizacion->iva_tasa ?: 0.16);
        $totales = app(Cotizador::class)->totales($aprobadas->map->toArray(), $ctx);
        $esSeguro = $cotizacion->esSeguro();

        $deAseguradora = fn (Partida $p) => $esSeguro && $this->enumPaga($p) === Paga::Aseguradora;
        [$cubiertas, $noCubiertas] = $aprobadas->partition($deAseguradora);

        $cabinaAseguradora = $esSeguro && $cubiertas->contains(fn (Partida $p) => $this->pintada($p));

        return DB::transaction(function () use ($orden, $usuario, $esSeguro, $cubiertas, $noCubiertas, $totales, $tasa, $cabinaAseguradora) {
            $facturas = collect();

            if ($esSeguro && $cubiertas->isNotEmpty()) {
                $a = $orden->aseguradora;
                $f = $this->crear($orden, $usuario, Receptor::aseguradora($a), 'PPD', FormaPago::PorDefinir, now()->addDays($a->dias_pago ?: 30));
                $this->conceptos($f, $cubiertas, $tasa, $cabinaAseguradora ? $totales['importe_cabina'] : 0, $totales['ciclos_cabina']);
                $this->descuentoProporcional($f, $totales['monto_aseguradora'], $tasa);
                $facturas->push($f->recalcular());
            }

            $cargoDeducible = $esSeguro ? round($totales['monto_cliente'] - $noCubiertas->sum('importe') * (1 + $tasa) - (! $cabinaAseguradora && $esSeguro ? $totales['importe_cabina'] * (1 + $tasa) : 0), 2) : 0;
            $cabinaCliente = ! $cabinaAseguradora ? $totales['importe_cabina'] : 0;

            if ($noCubiertas->isNotEmpty() || $cargoDeducible > 0.009 || $cabinaCliente > 0) {
                $credito = (int) ($orden->cliente->flotilla?->dias_credito ?? 0);
                $f = $this->crear($orden, $usuario, Receptor::cliente($orden->cliente), $credito > 0 ? 'PPD' : 'PUE',
                    $credito > 0 ? FormaPago::PorDefinir : $this->formaSugerida($orden), $credito > 0 ? now()->addDays($credito) : null);
                $this->conceptos($f, $noCubiertas, $tasa, $cabinaCliente, $totales['ciclos_cabina']);

                if ($cargoDeducible > 0.009) {
                    $this->concepto($f, null, 'otro', 'Deducible'.($orden->siniestro?->numero ? " del siniestro {$orden->siniestro->numero}" : '').' y demérito a cargo del asegurado', 1, round($cargoDeducible / (1 + $tasa), 2), $tasa);
                }
                $facturas->push($f->recalcular());
            }

            return $facturas;
        });
    }

    private function crear(OrdenServicio $orden, ?User $usuario, array $receptor, string $metodo, FormaPago $forma, $vence): Factura
    {
        $serie = config('tarzo.cfdi.serie');

        return Factura::create([
            ...$receptor,
            'tipo' => Factura::INGRESO, 'serie' => $serie, 'folio' => GeneradorFolio::numero("CFDI-{$serie}"),
            'orden_servicio_id' => $orden->id, 'metodo_pago' => $metodo, 'forma_pago' => $forma,
            'estado' => EstadoFactura::Borrador, 'vence' => $vence?->toDateString(), 'user_id' => $usuario?->id,
            'notas' => trim("Orden {$orden->folio} · {$orden->vehiculo->marca} {$orden->vehiculo->modelo} {$orden->vehiculo->placas}"),
        ]);
    }

    private function conceptos(Factura $f, Collection $partidas, float $tasa, float $cabina, int $ciclos): void
    {
        // El SAT no acepta conceptos con valor cero (p. ej. una partida sin tarifa capturada).
        foreach ($partidas->filter(fn (Partida $p) => (float) $p->importe > 0) as $p) {
            $tipo = $p->tipo instanceof TipoPartida ? $p->tipo : TipoPartida::from($p->tipo);
            $porPieza = in_array($tipo, [TipoPartida::Refaccion, TipoPartida::Otro], true) && (float) $p->cantidad > 0;
            $cantidad = $porPieza ? (float) $p->cantidad : 1;
            $this->concepto($f, $p, $tipo->value, $p->descripcion, $cantidad, (float) $p->importe, $tasa, $p->numero_parte);
        }

        if ($cabina > 0) {
            $this->concepto($f, null, 'hojalateria_pintura', "Horneado en cabina de pintura ({$ciclos} ".($ciclos === 1 ? 'ciclo' : 'ciclos').')', 1, $cabina, $tasa);
        }
    }

    private function concepto(Factura $f, ?Partida $p, string $tipo, string $descripcion, float $cantidad, float $importe, float $tasa, ?string $parte = null): void
    {
        [$clave, $unidad, $nombreUnidad] = config("tarzo.cfdi.claves.{$tipo}");
        $importe = round($importe, 2);

        $f->conceptos()->create([
            'partida_id' => $p?->id, 'clave_prod_serv' => $clave, 'clave_unidad' => $unidad, 'unidad' => $nombreUnidad,
            'no_identificacion' => $parte, 'descripcion' => mb_substr($descripcion, 0, 1000), 'cantidad' => $cantidad,
            'valor_unitario' => round($importe / $cantidad, 4), 'importe' => $importe, 'descuento' => 0,
            'iva_tasa' => $tasa, 'iva' => round($importe * $tasa, 2),
        ]);
    }

    /**
     * Reparte el descuento entre los conceptos para que el total cobrado sea $totalObjetivo (con IVA).
     */
    private function descuentoProporcional(Factura $f, float $totalObjetivo, float $tasa): void
    {
        $conceptos = $f->conceptos()->get();
        $subtotal = $conceptos->sum('importe');
        $descuento = round(min($subtotal, max(0, $subtotal - $totalObjetivo / (1 + $tasa))), 2);
        if ($descuento <= 0 || $subtotal <= 0) {
            return;
        }

        $restante = $descuento;
        foreach ($conceptos->values() as $i => $c) {
            $d = $i === $conceptos->count() - 1 ? $restante : round($descuento * $c->importe / $subtotal, 2);
            $d = min($d, $c->importe);
            $restante = round($restante - $d, 2);
            $c->update(['descuento' => $d, 'iva' => round(($c->importe - $d) * $tasa, 2)]);
        }
    }

    private function formaSugerida(OrdenServicio $orden): FormaPago
    {
        return $orden->pagos()->whereNull('cancelado_at')->latest('fecha')->first()?->forma_pago ?? FormaPago::Transferencia;
    }

    private function enumPaga(Partida $p): ?Paga
    {
        return $p->paga instanceof Paga ? $p->paga : Paga::tryFrom((string) $p->paga);
    }

    private function pintada(Partida $p): bool
    {
        $tipo = $p->tipo instanceof TipoPartida ? $p->tipo : TipoPartida::tryFrom((string) $p->tipo);
        $op = $p->operacion_hyp instanceof OperacionHyP ? $p->operacion_hyp : OperacionHyP::tryFrom((string) $p->operacion_hyp);

        return $tipo === TipoPartida::HojalateriaPintura && ($op ?? OperacionHyP::Reparar)->llevaPintura();
    }
}
