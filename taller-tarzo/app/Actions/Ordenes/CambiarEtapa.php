<?php

namespace App\Actions\Ordenes;

use App\Models\Etapa;
use App\Models\HistorialEtapa;
use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Mueve una orden a otra etapa del tablero y deja registro en el historial.
 *
 * Fase 0: solo valida permisos y registra. Las reglas de negocio por etapa
 * (cotización aprobada, checklist de calidad, refacciones pendientes) se
 * agregan en el Módulo 3 dentro de validarReglas().
 */
class CambiarEtapa
{
    public function __invoke(OrdenServicio $orden, Etapa $destino, ?User $usuario = null, ?string $comentario = null, bool $forzar = false): OrdenServicio
    {
        if ($usuario && ! $usuario->can('piso.gestionar')) {
            throw new AuthorizationException('No tienes permiso para mover órdenes en el tablero.');
        }

        if ($forzar && $usuario && ! $usuario->can('piso.forzar_etapa')) {
            throw new AuthorizationException('No tienes permiso para mover órdenes saltándose las reglas.');
        }

        if ($orden->etapa_id === $destino->id) {
            return $orden;
        }

        if (! $forzar) {
            $this->validarReglas($orden, $destino);
        }

        return DB::transaction(function () use ($orden, $destino, $usuario, $comentario, $forzar) {
            $anterior = $orden->etapa_id;

            $orden->forceFill([
                'etapa_id' => $destino->id,
                'etapa_desde' => now(),
            ])->save();

            HistorialEtapa::create([
                'orden_servicio_id' => $orden->id,
                'etapa_anterior_id' => $anterior,
                'etapa_id' => $destino->id,
                'user_id' => $usuario?->id,
                'comentario' => $comentario,
                'excepcion' => $forzar,
            ]);

            return $orden->refresh();
        });
    }

    protected function validarReglas(OrdenServicio $orden, Etapa $destino): void
    {
        // Reglas del Módulo 3 (ver Plan Maestro, historia KB-01).
    }
}
