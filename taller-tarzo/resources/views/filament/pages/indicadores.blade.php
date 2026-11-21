@php
    $d = $this->getDatos();
    $tendencia = $this->getTendencia();
    $op = $this->opciones();
    $fmt = function ($k) {
        $v = $k['valor'];
        if ($v === null) return '—';
        return match ($k['formato']) {
            'dinero' => '$'.number_format($v, 0),
            'porcentaje' => number_format($v, 1).' %',
            'dias' => number_format($v, 1).' días',
            'veces' => number_format($v, 2).'×',
            default => number_format($v),
        };
    };
    $maxDias = max(1, collect($d['etapas'])->max('dias') ?? 1);
    $maxIngreso = max(1, collect($tendencia)->max('ingreso') ?? 1);
    $maxEntregados = max(1, collect($tendencia)->max('entregados') ?? 1);
    $selStyle = 'border-radius:8px; border:1px solid var(--gray-300); padding:6px 28px 6px 10px; background-color:transparent; font-size:14px;';
@endphp
<x-filament-panels::page>
    <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
        <select wire:model.live="mes" style="{{ $selStyle }}" aria-label="Mes">
            @foreach ($op['meses'] as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
        </select>
        <select wire:model.live="tipo" style="{{ $selStyle }}" aria-label="Tipo de orden">
            <option value="">Todos los tipos</option>
            @foreach ($op['tipos'] as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
        </select>
        <select wire:model.live="aseguradora" style="{{ $selStyle }}" aria-label="Aseguradora">
            <option value="">Todas las aseguradoras</option>
            @foreach ($op['aseguradoras'] as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
        </select>
        <select wire:model.live="asesor" style="{{ $selStyle }}" aria-label="Asesor">
            <option value="">Todos los asesores</option>
            @foreach ($op['asesores'] as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
        </select>
        <span wire:loading style="font-size:13px; color:var(--gray-500)">Calculando…</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:12px;">
        @foreach ($d['kpis'] as $clave => $k)
            <x-filament::section :compact="true" wire:key="kpi-{{ $clave }}">
                <div style="font-size:13px; color:var(--gray-500)">{{ $k['nombre'] }}</div>
                <div style="font-size:26px; font-weight:700; margin:2px 0; color:var(--gray-950)" class="dark:text-white">{{ $fmt($k) }}</div>
                <div style="font-size:11.5px; line-height:1.35; color:var(--gray-500)">{{ $k['definicion'] }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:12px;">
        <x-filament::section :compact="true" heading="Días promedio por etapa">
            <x-slot name="description">
                @if ($d['cuello']) Cuello de botella: <strong>{{ $d['cuello']['etapa'] }}</strong> ({{ number_format($d['cuello']['dias'], 1) }} días) @else Sin movimientos en el periodo. @endif
            </x-slot>
            <div style="display:grid; gap:8px;">
                @foreach ($d['etapas'] as $e)
                    @php
                        $esCuello = $d['cuello'] && $e['etapa'] === $d['cuello']['etapa'];
                        $ancho = max(2, round($e['dias'] / $maxDias * 100));
                        $color = $esCuello ? 'var(--primary-600)' : 'var(--gray-300)';
                    @endphp
                    <div title="{{ $e['etapa'] }}: {{ number_format($e['dias'], 1) }} días promedio en {{ $e['ordenes'] }} {{ $e['ordenes'] === 1 ? 'orden' : 'órdenes' }}">
                        <div style="display:flex; justify-content:space-between; font-size:13px;">
                            <span>{{ $e['etapa'] }}</span><span style="color:var(--gray-600)">{{ number_format($e['dias'], 1) }} d · {{ $e['ordenes'] }}</span>
                        </div>
                        <div style="height:8px; background:var(--gray-100); border-radius:4px; margin-top:3px;">
                            <div style="height:8px; width:{{ $ancho }}%; background:{{ $color }}; border-radius:4px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section :compact="true" heading="Tendencia (6 meses)">
            <x-slot name="description">Ingreso facturado sin IVA y autos entregados.</x-slot>
            <table style="width:100%; font-size:13px; border-collapse:collapse;">
                <thead><tr style="color:var(--gray-500); font-size:12px; text-align:left;">
                    <th style="padding:4px">Mes</th><th style="padding:4px">Ingreso</th><th style="padding:4px; text-align:right">Utilidad</th><th style="padding:4px">Entregados</th>
                </tr></thead>
                <tbody>
                    @foreach ($tendencia as $t)
                        @php
                            $wIng = max(1, round($t['ingreso'] / $maxIngreso * 100));
                            $wEnt = max(1, round($t['entregados'] / $maxEntregados * 100));
                        @endphp
                        <tr style="border-top:1px solid var(--gray-100);">
                            <td style="padding:4px; white-space:nowrap">{{ $t['mes'] }}</td>
                            <td style="padding:4px; width:40%" title="Ingreso {{ $t['mes'] }}: ${{ number_format($t['ingreso'], 2) }}">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <div style="height:8px; width:{{ $wIng }}%; background:var(--primary-600); border-radius:0 4px 4px 0; min-width:2px"></div>
                                    <span style="white-space:nowrap">${{ number_format($t['ingreso'], 0) }}</span>
                                </div>
                            </td>
                            <td style="padding:4px; text-align:right; white-space:nowrap">${{ number_format($t['utilidad'], 0) }}</td>
                            <td style="padding:4px; width:22%" title="{{ $t['entregados'] }} autos entregados en {{ $t['mes'] }}">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <div style="height:8px; width:{{ $wEnt }}%; background:var(--gray-400); border-radius:0 4px 4px 0; min-width:2px"></div>
                                    <span>{{ $t['entregados'] }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    </div>

    <x-filament::section :compact="true" heading="Técnicos">
        <x-slot name="description">Eficiencia = horas estándar ÷ horas reales de las tareas terminadas. Productividad = horas en órdenes ÷ horas pagadas.</x-slot>
        <div style="overflow-x:auto">
            <table style="width:100%; font-size:13px; border-collapse:collapse;">
                <thead><tr style="color:var(--gray-500); font-size:12px; text-align:right;">
                    <th style="padding:6px; text-align:left">Técnico</th><th style="padding:6px">Horas reales</th><th style="padding:6px">Horas estándar</th><th style="padding:6px">Horas pagadas</th><th style="padding:6px">Eficiencia</th><th style="padding:6px">Productividad</th>
                </tr></thead>
                <tbody>
                    @forelse ($d['tecnicos'] as $t)
                        <tr style="border-top:1px solid var(--gray-100); text-align:right;">
                            <td style="padding:6px; text-align:left">{{ $t['tecnico'] }}</td>
                            <td style="padding:6px">{{ number_format($t['horas_reales'], 1) }}</td>
                            <td style="padding:6px">{{ number_format($t['horas_estandar'], 1) }}</td>
                            <td style="padding:6px">{{ number_format($t['horas_pagadas'], 0) }}</td>
                            <td style="padding:6px">{{ $t['eficiencia'] === null ? '—' : number_format($t['eficiencia'], 0).' %' }}</td>
                            <td style="padding:6px">{{ $t['productividad'] === null ? '—' : number_format($t['productividad'], 0).' %' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:6px; color:var(--gray-500)">Sin técnicos activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
