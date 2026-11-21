<?php

namespace App\Actions\Postventa;

use App\Actions\Ordenes\AbrirOrden;
use App\Enums\TipoOrden;
use App\Models\Garantia;
use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Abre una orden de tipo garantía (no se cobra y cuenta para la tasa de retorno).
 */
class ReclamarGarantia
{
    public function __invoke(Garantia $garantia, string $motivo, ?User $usuario = null): OrdenServicio
    {
        if ($garantia->estado !== 'vigente') {
            throw ValidationException::withMessages(['garantia' => 'La garantía ya no está vigente.']);
        }

        return DB::transaction(function () use ($garantia, $motivo, $usuario) {
            $orden = (new AbrirOrden)($garantia->vehiculo, TipoOrden::Garantia, $usuario, [
                'motivo_ingreso' => "Garantía de {$garantia->orden->folio} ({$garantia->tipo->getLabel()}): {$motivo}",
            ]);

            $garantia->update(['estado' => 'reclamada', 'orden_reclamo_id' => $orden->id]);

            return $orden;
        });
    }
}
