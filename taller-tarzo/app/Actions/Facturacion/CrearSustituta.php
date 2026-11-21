<?php

namespace App\Actions\Facturacion;

use App\Enums\EstadoFactura;
use App\Models\Factura;
use App\Models\User;
use App\Support\GeneradorFolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Copia una factura timbrada en un borrador con relación 04 (sustitución) para corregir datos.
 * Al timbrarla, la original se cancela con el motivo 01.
 */
class CrearSustituta
{
    public function __invoke(Factura $original, ?User $usuario = null): Factura
    {
        if ($original->tipo !== Factura::INGRESO || ! $original->timbrada()) {
            throw ValidationException::withMessages(['factura' => 'Solo se sustituyen facturas de ingreso timbradas.']);
        }

        return DB::transaction(function () use ($original, $usuario) {
            $nueva = $original->replicate(['uuid', 'pac_id', 'xml_path', 'pdf_path', 'timbrada_at', 'cancelada_at', 'motivo_cancelacion', 'estado_cancelacion', 'error', 'saldo']);
            $nueva->fill([
                'folio' => GeneradorFolio::numero("CFDI-{$original->serie}"), 'estado' => EstadoFactura::Borrador,
                'relacion_tipo' => '04', 'factura_relacionada_id' => $original->id, 'user_id' => $usuario?->id, 'saldo' => 0,
            ])->save();

            foreach ($original->conceptos as $c) {
                $nueva->conceptos()->create($c->replicate(['factura_id'])->toArray());
            }

            return $nueva->recalcular();
        });
    }
}
