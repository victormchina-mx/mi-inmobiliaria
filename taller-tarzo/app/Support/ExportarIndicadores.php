<?php

namespace App\Support;

use App\Models\CierreOrden;
use App\Services\Finanzas\Indicadores;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Libro de Excel con los KPIs del mes, días por etapa, técnicos y la utilidad de cada orden entregada.
 */
final class ExportarIndicadores
{
    public static function xlsx(Carbon $desde, Carbon $hasta, array $filtros = []): string
    {
        $datos = app(Indicadores::class)->calcular($desde, $hasta, $filtros);
        $base = tempnam(sys_get_temp_dir(), 'kpi');
        $ruta = $base.'.xlsx';

        $w = new Writer;
        $w->openToFile($ruta);
        $w->getCurrentSheet()->setName('Indicadores');
        $w->addRow(Row::fromValues(['Indicador', 'Valor', 'Unidad', 'Definición']));
        foreach ($datos['kpis'] as $k) {
            $w->addRow(Row::fromValues([$k['nombre'], $k['valor'], $k['formato'], $k['definicion']]));
        }

        $w->addNewSheetAndMakeItCurrent()->setName('Días por etapa');
        $w->addRow(Row::fromValues(['Etapa', 'Días promedio', 'Órdenes']));
        foreach ($datos['etapas'] as $e) {
            $w->addRow(Row::fromValues([$e['etapa'], $e['dias'], $e['ordenes']]));
        }

        $w->addNewSheetAndMakeItCurrent()->setName('Técnicos');
        $w->addRow(Row::fromValues(['Técnico', 'Horas reales', 'Horas estándar', 'Horas pagadas', 'Eficiencia %', 'Productividad %']));
        foreach ($datos['tecnicos'] as $t) {
            $w->addRow(Row::fromValues([$t['tecnico'], $t['horas_reales'], $t['horas_estandar'], $t['horas_pagadas'], $t['eficiencia'], $t['productividad']]));
        }

        $w->addNewSheetAndMakeItCurrent()->setName('Utilidad por orden');
        $w->addRow(Row::fromValues(['Orden', 'Tipo', 'Entrega', 'Ingreso', 'Refacciones', 'Materiales', 'Mano de obra', 'Subcontratos', 'Utilidad', 'Margen %', 'Ingreso estimado']));
        CierreOrden::with('orden')->whereHas('orden', fn ($o) => $o->whereBetween('fecha_entrega', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($filtros['aseguradora_id'] ?? null, fn ($q, $v) => $q->where('aseguradora_id', $v))
            ->when($filtros['asesor_id'] ?? null, fn ($q, $v) => $q->where('asesor_id', $v)))
            ->get()->each(fn (CierreOrden $c) => $w->addRow(Row::fromValues([
                $c->orden->folio, $c->orden->tipo->getLabel(), $c->orden->fecha_entrega?->format('Y-m-d'), $c->ingreso, $c->costo_refacciones,
                $c->costo_materiales, $c->costo_mano_obra, $c->costo_subcontratos, $c->utilidad, $c->margen, $c->ingreso_estimado ? 'Sí' : 'No',
            ])));

        $w->close();
        $contenido = (string) file_get_contents($ruta);
        @unlink($ruta);
        @unlink($base);

        return $contenido;
    }
}
