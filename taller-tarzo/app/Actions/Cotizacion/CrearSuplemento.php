<?php

namespace App\Actions\Cotizacion;

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\Cotizacion\Cotizador;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nueva versión de la cotización para agregar partidas descubiertas al desarmar.
 * Copia todas las partidas conservando su autorización: las ya aprobadas no se vuelven
 * a pedir y no pueden modificarse. La versión anterior queda como "reemplazada" en el historial.
 */
class CrearSuplemento
{
    public function __invoke(Cotizacion $anterior, ?User $usuario = null): Cotizacion
    {
        if ($anterior->estado->editable()) {
            throw ValidationException::withMessages(['cotizacion' => 'La cotización sigue en borrador: edítala directamente.']);
        }

        if ($anterior->orden->cotizacionActual()->value('id') !== $anterior->id) {
            throw ValidationException::withMessages(['cotizacion' => 'Solo se puede crear un suplemento de la versión más reciente.']);
        }

        return DB::transaction(function () use ($anterior, $usuario) {
            $version = $anterior->version + 1;

            $nueva = $anterior->replicate([
                'folio', 'version', 'estado', 'enviada_at', 'respondida_at', 'recordatorio_at', 'user_id',
            ]);
            $nueva->fill([
                'version' => $version,
                'folio' => "{$anterior->orden->folio}-C{$version}",
                'estado' => EstadoCotizacion::Borrador,
                'user_id' => $usuario?->id ?? $anterior->user_id,
            ])->save();

            foreach ($anterior->partidas as $partida) {
                $copia = $partida->replicate(['cotizacion_id', 'aprobacion_id']);
                $copia->cotizacion_id = $nueva->id;
                $copia->aprobacion_id = $partida->aprobacion_id; // conserva la evidencia de quién la aprobó
                $copia->save();

                // Las tareas de taller ya generadas siguen a la copia (no se duplican).
                Tarea::where('partida_id', $partida->id)->update(['partida_id' => $copia->id]);
            }

            $anterior->update(['estado' => EstadoCotizacion::Reemplazada]);

            return app(Cotizador::class)->recalcular($nueva);
        });
    }
}
