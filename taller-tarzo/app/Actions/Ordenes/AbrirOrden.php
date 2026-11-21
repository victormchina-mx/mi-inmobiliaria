<?php

namespace App\Actions\Ordenes;

use App\Enums\TipoOrden;
use App\Models\Etapa;
use App\Models\HistorialEtapa;
use App\Models\OrdenServicio;
use App\Models\User;
use App\Models\Vehiculo;
use App\Support\GeneradorFolio;
use Illuminate\Support\Facades\DB;

/**
 * Abre una orden de servicio en la etapa de Recepción con folio consecutivo.
 */
class AbrirOrden
{
    /**
     * @param  array<string, mixed>  $datos  campos adicionales de la orden
     */
    public function __invoke(Vehiculo $vehiculo, TipoOrden $tipo, ?User $asesor = null, array $datos = []): OrdenServicio
    {
        return DB::transaction(function () use ($vehiculo, $tipo, $asesor, $datos) {
            $etapa = Etapa::inicial();
            $ahora = now();

            $orden = OrdenServicio::create([
                ...$datos,
                'folio' => GeneradorFolio::siguiente('OS'),
                'tipo' => $tipo,
                'cliente_id' => $vehiculo->cliente_id,
                'vehiculo_id' => $vehiculo->id,
                'asesor_id' => $asesor?->id,
                'etapa_id' => $etapa->id,
                'fecha_ingreso' => $ahora,
                'etapa_desde' => $ahora,
                'kilometraje_ingreso' => $datos['kilometraje_ingreso'] ?? $vehiculo->kilometraje,
            ]);

            HistorialEtapa::create([
                'orden_servicio_id' => $orden->id,
                'etapa_id' => $etapa->id,
                'user_id' => $asesor?->id,
                'comentario' => 'Ingreso del vehículo',
            ]);

            if (isset($datos['kilometraje_ingreso']) && $datos['kilometraje_ingreso'] > (int) $vehiculo->kilometraje) {
                $vehiculo->update(['kilometraje' => $datos['kilometraje_ingreso']]);
            }

            return $orden;
        });
    }
}
