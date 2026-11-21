<?php

namespace App\Support;

use App\Enums\EstadoTarea;
use App\Enums\TipoOrden;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Datos del tablero Kanban (los usa la página del panel y el modo TV).
 */
final class DatosTablero
{
    /**
     * @param  array{asesor_id?:mixed, aseguradora_id?:mixed, tipo?:mixed, tecnico_id?:mixed, buscar?:mixed}  $filtros
     * @return Collection<int, array{etapa: Etapa, ordenes: Collection<int, OrdenServicio>, excedido: bool}>
     */
    public static function columnas(array $filtros = []): Collection
    {
        $etapas = Etapa::ordenadas()->where('es_final', false)->get();

        $ordenes = OrdenServicio::query()
            ->whereNull('cerrada_at')
            ->with(['etapa', 'vehiculo', 'cliente', 'aseguradora', 'asesor', 'tareas.tecnico'])
            ->when($filtros['asesor_id'] ?? null, fn (Builder $q, $v) => $q->where('asesor_id', $v))
            ->when($filtros['aseguradora_id'] ?? null, fn (Builder $q, $v) => $q->where('aseguradora_id', $v))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $v) => $q->where('tipo', $v))
            ->when($filtros['tecnico_id'] ?? null, fn (Builder $q, $v) => $q->whereHas('tareas', fn (Builder $t) => $t->where('tecnico_id', $v)))
            ->when(filled($filtros['buscar'] ?? null), function (Builder $q) use ($filtros) {
                $t = '%'.trim($filtros['buscar']).'%';
                $placas = '%'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $filtros['buscar'])).'%';
                $q->where(fn (Builder $w) => $w->where('folio', 'like', $t)
                    ->orWhereHas('vehiculo', fn (Builder $v) => $v->where('placas', 'like', $placas)->orWhere('modelo', 'like', $t))
                    ->orWhereHas('cliente', fn (Builder $c) => $c->where('nombre', 'like', $t)->orWhere('apellidos', 'like', $t)));
            })
            ->orderBy('etapa_desde')
            ->get()
            ->groupBy('etapa_id');

        return $etapas->map(fn (Etapa $e) => [
            'etapa' => $e,
            'ordenes' => $ordenes->get($e->id, collect()),
            'excedido' => $e->limite_autos && $ordenes->get($e->id, collect())->count() > $e->limite_autos,
        ]);
    }

    /**
     * Semáforo de la tarjeta según los días en la etapa: ok | cerca | excedido.
     */
    public static function semaforo(OrdenServicio $orden): string
    {
        $max = $orden->etapa?->dias_max;

        if (! $max) {
            return 'ok';
        }

        $dias = $orden->diasEnEtapa();

        return match (true) {
            $dias > $max => 'excedido',
            $dias >= $max - 1 && $max > 1 => 'cerca',
            default => 'ok',
        };
    }

    /**
     * @return array{hechas:int, total:int, tecnicos: list<string>}
     */
    public static function avance(OrdenServicio $orden): array
    {
        $tareas = $orden->tareas;

        return [
            'hechas' => $tareas->where('estado', EstadoTarea::Terminada)->count(),
            'total' => $tareas->count(),
            'tecnicos' => $tareas->pluck('tecnico.nombre')->filter()->unique()
                ->map(fn ($n) => collect(explode(' ', $n))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''))
                ->values()->all(),
        ];
    }

    public static function promesaVencida(OrdenServicio $orden): bool
    {
        return $orden->fecha_promesa !== null && $orden->fecha_promesa->isPast();
    }

    public static function etiquetaTipo(OrdenServicio $orden): string
    {
        return $orden->tipo === TipoOrden::Seguro && $orden->aseguradora ? $orden->aseguradora->nombre : $orden->tipo->getLabel();
    }
}
