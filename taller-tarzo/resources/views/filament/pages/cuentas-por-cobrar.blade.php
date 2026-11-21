@php
    $filas = $this->getAntiguedad();
    $rangos = \App\Filament\Pages\CuentasPorCobrar::RANGOS;
    $totales = collect(array_keys($rangos))->mapWithKeys(fn ($r) => [$r => $filas->sum($r)]);
    $mx = fn ($n) => '$'.number_format((float) $n, 2);
@endphp
<x-filament-panels::page>
    <x-filament::section :compact="true" heading="Antigüedad de saldos">
        @if ($filas->isEmpty())
            <p style="color:var(--gray-500)">No hay facturas pendientes de cobro.</p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%; font-size:14px; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:right; color:var(--gray-500); font-size:12px;">
                            <th style="text-align:left; padding:6px">Cartera</th>
                            @foreach ($rangos as $etiqueta)
                                <th style="padding:6px">{{ $etiqueta }}</th>
                            @endforeach
                            <th style="padding:6px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $grupo => $f)
                            <tr style="text-align:right; border-top:1px solid var(--gray-100);">
                                <td style="text-align:left; padding:6px">{{ $grupo }} <span style="color:var(--gray-500)">({{ $f['n'] }})</span></td>
                                @foreach (array_keys($rangos) as $i => $r)
                                    @php $color = $f[$r] > 0 && $i >= 2 ? 'var(--danger-600)' : ($f[$r] > 0 && $i === 1 ? 'var(--warning-600)' : 'inherit'); @endphp
                                    <td style="padding:6px; color:{{ $color }}">{{ $f[$r] > 0 ? $mx($f[$r]) : '—' }}</td>
                                @endforeach
                                <td style="padding:6px; font-weight:600">{{ $mx($f['total']) }}</td>
                            </tr>
                        @endforeach
                        <tr style="text-align:right; border-top:2px solid var(--gray-300); font-weight:600;">
                            <td style="text-align:left; padding:6px">Total</td>
                            @foreach ($totales as $t)
                                <td style="padding:6px">{{ $mx($t) }}</td>
                            @endforeach
                            <td style="padding:6px">{{ $mx($totales->sum()) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
