<?php

namespace App\Actions\Piso;

use App\Enums\EstadoPartida;
use App\Enums\OperacionHyP;
use App\Enums\TipoPartida;
use App\Models\Area;
use App\Models\Cotizacion;
use App\Models\Partida;
use App\Models\Tarea;

/**
 * Convierte las partidas autorizadas en tareas de taller. Es idempotente:
 * una partida genera una tarea por área (laminado y pintura van por separado).
 */
class GenerarTareas
{
    /**
     * @return int tareas nuevas
     */
    public function __invoke(Cotizacion $cotizacion): int
    {
        $areas = Area::whereIn('clave', ['hojalateria', 'pintura'])->pluck('id', 'clave');
        $nuevas = 0;

        $partidas = $cotizacion->partidas()->where('estado', EstadoPartida::Aprobada->value)->get();

        foreach ($partidas as $partida) {
            foreach ($this->trabajos($partida, $areas->all()) as [$areaId, $descripcion, $horas]) {
                $tarea = Tarea::firstOrCreate(
                    ['partida_id' => $partida->id, 'area_id' => $areaId],
                    [
                        'orden_servicio_id' => $cotizacion->orden_servicio_id,
                        'descripcion' => $descripcion,
                        'horas_estandar' => $horas,
                        'posicion' => $partida->orden,
                    ],
                );
                $tarea->wasRecentlyCreated && $nuevas++;
            }
        }

        return $nuevas;
    }

    /**
     * @param  array<string, int>  $areas
     * @return list<array{0:int|null,1:string,2:float}>
     */
    private function trabajos(Partida $p, array $areas): array
    {
        return match ($p->tipo) {
            TipoPartida::ManoObra => [[$p->area_id, $p->descripcion, (float) $p->horas]],
            TipoPartida::HojalateriaPintura => array_values(array_filter([
                ($p->horas_laminado ?? 0) > 0 || $p->operacion_hyp === OperacionHyP::DesmontarMontar
                    ? [$areas['hojalateria'] ?? null, ($p->operacion_hyp === OperacionHyP::Sustituir ? 'Sustituir: ' : 'Laminado: ').$p->descripcion, (float) $p->horas_laminado]
                    : null,
                ($p->horas_pintura ?? 0) > 0 && $p->operacion_hyp?->llevaPintura()
                    ? [$areas['pintura'] ?? null, 'Pintura: '.$p->descripcion, (float) $p->horas_pintura]
                    : null,
            ])),
            default => [],
        };
    }
}
