<?php

namespace App\Services\Finanzas;

use App\Enums\EstadoFactura;
use App\Enums\EstadoPartida;
use App\Enums\TipoMovimiento;
use App\Enums\TipoOrden;
use App\Models\EncuestaNps;
use App\Models\Etapa;
use App\Models\Factura;
use App\Models\HistorialEtapa;
use App\Models\MovimientoInventario;
use App\Models\OrdenServicio;
use App\Models\Partida;
use App\Models\Producto;
use App\Models\RegistroTiempo;
use App\Models\Tarea;
use App\Models\Tecnico;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * KPIs del taller para un periodo. Cada indicador lleva su definición, que se muestra en el tablero.
 *
 * Filtros: tipo de orden, aseguradora y asesor (aplican a lo que depende de una orden).
 */
class Indicadores
{
    /** @var array{tipo?: ?string, aseguradora_id?: ?int, asesor_id?: ?int} */
    private array $filtros = [];

    private Carbon $desde;

    private Carbon $hasta;

    /**
     * @param  array{tipo?: ?string, aseguradora_id?: ?int, asesor_id?: ?int}  $filtros
     * @return array<string, mixed>
     */
    public function calcular(Carbon $desde, Carbon $hasta, array $filtros = []): array
    {
        $this->desde = $desde->copy()->startOfDay();
        $this->hasta = $hasta->copy()->endOfDay();
        $this->filtros = array_filter($filtros);

        $entregadas = $this->ordenes()->whereBetween('fecha_entrega', [$this->desde, $this->hasta])->with('cierre')->get();
        $conPromesa = $entregadas->filter(fn ($o) => $o->fecha_promesa);
        $cierres = $entregadas->pluck('cierre')->filter();
        $ingreso = $this->ingresoFacturado();
        $tecnicos = $this->porTecnico();
        $horasReales = $tecnicos->sum('horas_reales');
        $horasEstandar = $tecnicos->sum('horas_estandar');
        $realesTerminadas = $tecnicos->sum('reales_terminadas');
        $horasPagadas = $tecnicos->sum('horas_pagadas');
        $garantias = $this->ordenes()->where('tipo', TipoOrden::Garantia->value)->whereBetween('fecha_ingreso', [$this->desde, $this->hasta])->count();
        [$cotizado, $aprobado] = $this->aprobacion();
        $etapas = $this->diasPorEtapa();
        $consumo = MovimientoInventario::where('tipo', TipoMovimiento::Salida->value)->whereBetween('created_at', [$this->desde, $this->hasta])->sum('costo_total');
        $inventario = Producto::query()->selectRaw('sum(existencia * costo_promedio) as v')->value('v');
        $nps = EncuestaNps::whereBetween('respondida_at', [$this->desde, $this->hasta])
            ->when($this->filtros, fn ($query) => $query->whereHas('orden', fn ($o) => $this->aplicarFiltros($o)))->pluck('calificacion');

        $kpis = [
            'ciclo' => $this->kpi('Ciclo de reparación', $entregadas->isNotEmpty() ? round($entregadas->avg(fn ($o) => $o->fecha_ingreso->diffInHours($o->fecha_entrega) / 24), 1) : null, 'dias',
                'Días promedio entre el ingreso y la entrega de las órdenes entregadas en el periodo.'),
            'entregados' => $this->kpi('Autos entregados', $entregadas->count(), 'numero', 'Órdenes entregadas al cliente en el periodo.'),
            'ingreso' => $this->kpi('Ingreso facturado', $ingreso, 'dinero', 'Facturas timbradas − notas de crédito del periodo, sin IVA.'),
            'ticket' => $this->kpi('Ticket promedio', $cierres->isNotEmpty() ? round($cierres->avg('ingreso'), 2) : null, 'dinero',
                'Ingreso promedio (sin IVA) de las órdenes entregadas en el periodo.'),
            'margen' => $this->kpi('Margen', $cierres->sum('ingreso') > 0 ? round($cierres->sum('utilidad') / $cierres->sum('ingreso') * 100, 1) : null, 'porcentaje',
                'Utilidad ÷ ingreso de las órdenes entregadas (refacciones, materiales, mano de obra y subcontratos reales).'),
            'utilidad' => $this->kpi('Utilidad', round($cierres->sum('utilidad'), 2), 'dinero', 'Suma de la utilidad de las órdenes entregadas en el periodo.'),
            'promesa' => $this->kpi('Cumplimiento de fecha promesa', $conPromesa->isNotEmpty() ? round($conPromesa->filter(fn ($o) => $o->fecha_entrega->lte($o->fecha_promesa))->count() / $conPromesa->count() * 100, 1) : null, 'porcentaje',
                'Entregas a tiempo ÷ entregas con fecha promesa.'),
            'retorno' => $this->kpi('Tasa de retorno', $entregadas->count() ? round($garantias / $entregadas->count() * 100, 1) : null, 'porcentaje',
                'Órdenes de garantía abiertas ÷ órdenes entregadas en el periodo. Mide la calidad del trabajo.'),
            'eficiencia' => $this->kpi('Eficiencia', $realesTerminadas > 0 ? round($horasEstandar / $realesTerminadas * 100, 1) : null, 'porcentaje',
                'Horas estándar de las tareas terminadas ÷ horas reales trabajadas. Más de 100 % = más rápido que el estándar.'),
            'productividad' => $this->kpi('Productividad', $horasPagadas > 0 ? round($horasReales / $horasPagadas * 100, 1) : null, 'porcentaje',
                'Horas trabajadas en órdenes ÷ horas pagadas (capacidad diaria × días laborables lun–sáb). Muestra el tiempo muerto.'),
            'aprobacion' => $this->kpi('Tasa de aprobación', $cotizado > 0 ? round($aprobado / $cotizado * 100, 1) : null, 'porcentaje',
                'Monto autorizado ÷ monto cotizado de los presupuestos respondidos en el periodo.'),
            'nps' => $this->kpi('NPS', EncuestaNps::calcular($nps), 'numero', '% promotores (9–10) − % detractores (0–6) de las encuestas respondidas.'),
            'rotacion' => $this->kpi('Rotación de inventario', $inventario > 0 ? round($consumo / $inventario, 2) : null, 'veces',
                'Costo de lo consumido en el periodo ÷ valor actual del inventario. Bajo = dinero detenido en almacén.'),
        ];

        $cuello = collect($etapas)->sortByDesc('dias')->first();

        return [
            'kpis' => $kpis,
            'etapas' => $etapas,
            'cuello' => $cuello,
            'tecnicos' => $tecnicos->values()->all(),
        ];
    }

    /**
     * Ingreso, utilidad y autos entregados de los últimos meses (sin filtros de periodo).
     *
     * @return list<array{mes: string, ingreso: float, utilidad: float, entregados: int}>
     */
    public function tendencia(int $meses = 6, array $filtros = []): array
    {
        $this->filtros = array_filter($filtros);
        $salida = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $inicio = now()->startOfMonth()->subMonthsNoOverflow($i);
            $this->desde = $inicio->copy();
            $this->hasta = $inicio->copy()->endOfMonth();
            $entregadas = $this->ordenes()->whereBetween('fecha_entrega', [$this->desde, $this->hasta])->with('cierre')->get();

            $salida[] = [
                'mes' => $inicio->translatedFormat('M Y'),
                'ingreso' => $this->ingresoFacturado(),
                'utilidad' => round($entregadas->pluck('cierre')->filter()->sum('utilidad'), 2),
                'entregados' => $entregadas->count(),
            ];
        }

        return $salida;
    }

    private function kpi(string $nombre, float|int|null $valor, string $formato, string $definicion): array
    {
        return compact('nombre', 'valor', 'formato', 'definicion');
    }

    private function ordenes(): Builder
    {
        return $this->aplicarFiltros(OrdenServicio::query());
    }

    private function aplicarFiltros(Builder $query): Builder
    {
        return $query
            ->when($this->filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($this->filtros['aseguradora_id'] ?? null, fn ($q, $v) => $q->where('aseguradora_id', $v))
            ->when($this->filtros['asesor_id'] ?? null, fn ($q, $v) => $q->where('asesor_id', $v));
    }

    private function ingresoFacturado(): float
    {
        return round((float) Factura::query()->whereIn('tipo', [Factura::INGRESO, Factura::EGRESO])
            ->where('estado', EstadoFactura::Timbrada->value)->whereBetween('timbrada_at', [$this->desde, $this->hasta])
            ->when($this->filtros, fn ($query) => $query->whereHas('orden', fn ($o) => $this->aplicarFiltros($o)))
            ->get()->sum(fn (Factura $f) => ($f->tipo === Factura::EGRESO ? -1 : 1) * ($f->subtotal - $f->descuento)), 2);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function aprobacion(): array
    {
        $partidas = Partida::query()->whereIn('estado', [EstadoPartida::Aprobada->value, EstadoPartida::Rechazada->value])
            ->whereHas('cotizacion', fn ($c) => $c->whereBetween('respondida_at', [$this->desde, $this->hasta])
                ->whereHas('orden', fn ($o) => $this->aplicarFiltros($o)))
            ->get(['importe', 'estado']);

        // Los suplementos copian partidas: se cuentan las de la versión respondida en el periodo.
        return [(float) $partidas->sum('importe'), (float) $partidas->where('estado', EstadoPartida::Aprobada)->sum('importe')];
    }

    /**
     * Días promedio que una orden pasa en cada etapa (estancias que terminaron en el periodo).
     *
     * @return list<array{etapa: string, dias: float, ordenes: int}>
     */
    public function diasPorEtapa(): array
    {
        $historial = HistorialEtapa::query()
            ->whereHas('orden', fn ($o) => $this->aplicarFiltros($o->withTrashed()))
            ->whereIn('orden_servicio_id', HistorialEtapa::query()->select('orden_servicio_id')->whereBetween('created_at', [$this->desde, $this->hasta]))
            ->orderBy('orden_servicio_id')->orderBy('created_at')->orderBy('id')
            ->get(['orden_servicio_id', 'etapa_id', 'created_at']);

        $estancias = [];
        foreach ($historial->groupBy('orden_servicio_id') as $movs) {
            $movs = $movs->values();
            foreach ($movs as $i => $m) {
                $sale = $movs[$i + 1]->created_at ?? null;
                if ($sale && $sale->between($this->desde, $this->hasta)) {
                    $estancias[$m->etapa_id][] = $m->created_at->diffInHours($sale) / 24;
                }
            }
        }

        $nombres = Etapa::orderBy('orden')->pluck('nombre', 'id');

        return $nombres->filter(fn ($n, $id) => isset($estancias[$id]))
            ->map(fn ($nombre, $id) => ['etapa' => $nombre, 'dias' => round(array_sum($estancias[$id]) / count($estancias[$id]), 1), 'ordenes' => count($estancias[$id])])
            ->values()->all();
    }

    /**
     * @return Collection<int, array{tecnico: string, horas_reales: float, horas_estandar: float, horas_pagadas: float, eficiencia: ?float, productividad: ?float}>
     */
    private function porTecnico(): Collection
    {
        $laborables = collect(CarbonPeriod::create($this->desde, min($this->hasta, now()->endOfDay())))->filter(fn ($d) => ! $d->isSunday())->count();

        $minutos = RegistroTiempo::query()->whereNotNull('fin')->whereBetween('inicio', [$this->desde, $this->hasta])
            ->when($this->filtros, fn ($query) => $query->whereHas('tarea.orden', fn ($o) => $this->aplicarFiltros($o)))
            ->selectRaw('tecnico_id, sum(minutos) as m')->groupBy('tecnico_id')->pluck('m', 'tecnico_id');

        $estandar = Tarea::query()->whereNotNull('terminada_at')->whereBetween('terminada_at', [$this->desde, $this->hasta])
            ->when($this->filtros, fn ($query) => $query->whereHas('orden', fn ($o) => $this->aplicarFiltros($o)))
            ->selectRaw('tecnico_id, sum(horas_estandar) as h')->groupBy('tecnico_id')->pluck('h', 'tecnico_id');

        // Horas reales de las tareas terminadas (para que la eficiencia compare lo mismo).
        $realesTerminadas = RegistroTiempo::query()->whereNotNull('fin')
            ->whereHas('tarea', fn ($t) => $t->whereBetween('terminada_at', [$this->desde, $this->hasta]))
            ->selectRaw('tecnico_id, sum(minutos) as m')->groupBy('tecnico_id')->pluck('m', 'tecnico_id');

        return Tecnico::where('activo', true)->orderBy('nombre')->get()->map(function (Tecnico $t) use ($minutos, $estandar, $realesTerminadas, $laborables) {
            $reales = round(($minutos[$t->id] ?? 0) / 60, 2);
            $std = round((float) ($estandar[$t->id] ?? 0), 2);
            $realesStd = ($realesTerminadas[$t->id] ?? 0) / 60;
            $pagadas = round($laborables * (float) $t->capacidad_horas_dia, 2);

            return [
                'tecnico' => $t->nombre,
                'horas_reales' => $reales,
                'horas_estandar' => $std,
                'horas_pagadas' => $pagadas,
                'reales_terminadas' => round($realesStd, 2),
                'eficiencia' => $realesStd > 0 ? round($std / $realesStd * 100, 1) : null,
                'productividad' => $pagadas > 0 ? round($reales / $pagadas * 100, 1) : null,
            ];
        });
    }
}
