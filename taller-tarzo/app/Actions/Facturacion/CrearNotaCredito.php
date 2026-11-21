<?php

namespace App\Actions\Facturacion;

use App\Enums\EstadoFactura;
use App\Enums\FormaPago;
use App\Models\Factura;
use App\Models\User;
use App\Support\GeneradorFolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nota de crédito (CFDI de egreso, relación 01) por descuento, bonificación o devolución sobre una factura.
 */
class CrearNotaCredito
{
    public function __invoke(Factura $original, float $montoConIva, string $motivo, ?User $usuario = null): Factura
    {
        if ($original->tipo !== Factura::INGRESO || ! $original->timbrada()) {
            throw ValidationException::withMessages(['factura' => 'Solo se emiten notas de crédito sobre facturas timbradas.']);
        }
        if ($montoConIva <= 0 || $montoConIva > $original->saldo + 0.009) {
            throw ValidationException::withMessages(['monto' => 'El monto debe ser mayor a cero y no mayor al saldo de la factura ($'.number_format($original->saldo, 2).').']);
        }

        return DB::transaction(function () use ($original, $montoConIva, $motivo, $usuario) {
            $tasa = (float) ($original->conceptos()->value('iva_tasa') ?? 0.16);
            $serie = config('tarzo.cfdi.serie_nota_credito');
            $base = round($montoConIva / (1 + $tasa), 2);

            $nota = Factura::create([
                ...$original->only(['orden_servicio_id', 'receptor_tipo', 'cliente_id', 'aseguradora_id', 'receptor_rfc', 'receptor_nombre', 'receptor_regimen', 'receptor_cp', 'uso_cfdi', 'email']),
                'tipo' => Factura::EGRESO, 'serie' => $serie, 'folio' => GeneradorFolio::numero("CFDI-{$serie}"),
                'metodo_pago' => 'PUE',
                'forma_pago' => $original->forma_pago === FormaPago::PorDefinir ? FormaPago::Condonacion : $original->forma_pago,
                'estado' => EstadoFactura::Borrador, 'relacion_tipo' => '01', 'factura_relacionada_id' => $original->id,
                'notas' => $motivo, 'user_id' => $usuario?->id,
            ]);

            [$clave, $unidad, $nombre] = config('tarzo.cfdi.claves.nota_credito');
            $nota->conceptos()->create([
                'clave_prod_serv' => $clave, 'clave_unidad' => $unidad, 'unidad' => $nombre,
                'descripcion' => mb_substr("{$motivo} · factura {$original->folio_completo}", 0, 1000),
                'cantidad' => 1, 'valor_unitario' => $base, 'importe' => $base, 'descuento' => 0, 'iva_tasa' => $tasa,
                'iva' => round($montoConIva - $base, 2),
            ]);

            return $nota->recalcular();
        });
    }
}
