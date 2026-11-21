<?php

namespace App\Actions\Piso;

use App\Enums\MomentoEvidencia;
use App\Jobs\MarcarEvidencia;
use App\Models\Evidencia;
use App\Models\OrdenServicio;
use App\Models\Tarea;
use App\Models\User;

/**
 * Registra fotos de evidencia en la etapa actual de la orden y las marca con folio y fecha.
 */
class RegistrarEvidencia
{
    /**
     * @param  list<string>  $archivos  rutas ya guardadas en el disco de media
     * @return list<Evidencia>
     */
    public function __invoke(OrdenServicio $orden, array $archivos, MomentoEvidencia $momento, ?User $usuario = null, ?Tarea $tarea = null, ?string $descripcion = null, bool $visibleCliente = false): array
    {
        $creadas = [];

        foreach ($archivos as $archivo) {
            $evidencia = Evidencia::create([
                'orden_servicio_id' => $orden->id,
                'etapa_id' => $orden->etapa_id,
                'tarea_id' => $tarea?->id,
                'user_id' => $usuario?->id,
                'momento' => $momento,
                'descripcion' => $descripcion ?? $tarea?->descripcion,
                'archivo' => $archivo,
                'visible_cliente' => $visibleCliente,
            ]);

            MarcarEvidencia::dispatch($evidencia->id);
            $creadas[] = $evidencia;
        }

        return $creadas;
    }
}
