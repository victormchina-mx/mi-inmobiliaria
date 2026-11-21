<?php

namespace App\Actions\Facturacion;

use App\Models\Pago;
use App\Services\Cfdi\Facturador;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancela un cobro registrado por error: cancela su REP y devuelve el saldo a las facturas.
 */
class CancelarPago
{
    public function __construct(private Facturador $facturador) {}

    public function __invoke(Pago $pago, string $motivo): Pago
    {
        if ($pago->cancelado_at) {
            throw ValidationException::withMessages(['pago' => 'El pago ya está cancelado.']);
        }

        if ($rep = $pago->complemento()->first()) {
            $rep->timbrada() ? $this->facturador->cancelar($rep, '02') : $rep->delete();
        }

        return DB::transaction(function () use ($pago, $motivo) {
            foreach ($pago->aplicaciones()->with('factura')->get() as $a) {
                $a->factura->forceFill(['saldo' => round($a->factura->saldo + $a->monto, 2)])->save();
                $a->delete();
            }

            $pago->forceFill(['cancelado_at' => now(), 'motivo_cancelacion' => $motivo, 'por_aplicar' => 0])->save();

            return $pago;
        });
    }
}
