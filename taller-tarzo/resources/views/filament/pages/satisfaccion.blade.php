@php
    $r = $this->getResumen();
    $porAsesor = $this->getPor('asesor');
    $porAseguradora = $this->getPor('aseguradora');
    $colorNps = $r['nps'] === null ? 'var(--gray-500)' : ($r['nps'] >= 50 ? 'var(--success-600)' : ($r['nps'] >= 0 ? 'var(--warning-600)' : 'var(--danger-600)'));
@endphp
<x-filament-panels::page>
    <div style="display:flex; gap:8px; align-items:center;">
        <span style="font-size:14px; color:var(--gray-500)">Periodo:</span>
        @foreach ([30 => '30 días', 90 => '90 días', 365 => '12 meses'] as $d => $etiqueta)
            <x-filament::button size="sm" :color="$dias === $d ? 'primary' : 'gray'" wire:click="$set('dias', {{ $d }})">{{ $etiqueta }}</x-filament::button>
        @endforeach
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
        <x-filament::section :compact="true">
            <div style="font-size:13px; color:var(--gray-500)">NPS</div>
            <div style="font-size:32px; font-weight:700; color:{{ $colorNps }}">{{ $r['nps'] ?? '—' }}</div>
            <div style="font-size:12px; color:var(--gray-500)">de −100 a 100</div>
        </x-filament::section>
        <x-filament::section :compact="true">
            <div style="font-size:13px; color:var(--gray-500)">Respuestas</div>
            <div style="font-size:32px; font-weight:700">{{ $r['respuestas'] }}</div>
            <div style="font-size:12px; color:var(--gray-500)">de {{ $r['enviadas'] }} enviadas{{ $r['tasa'] !== null ? ' ('.$r['tasa'].' %)' : '' }}</div>
        </x-filament::section>
        <x-filament::section :compact="true">
            <div style="font-size:13px; color:var(--gray-500)">Promotores · pasivos · detractores</div>
            <div style="font-size:24px; font-weight:700; margin-top:6px">
                <span style="color:var(--success-600)">{{ $r['promotores'] }}</span> ·
                <span style="color:var(--warning-600)">{{ $r['pasivos'] }}</span> ·
                <span style="color:var(--danger-600)">{{ $r['detractores'] }}</span>
            </div>
        </x-filament::section>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px;">
        @foreach (['Por asesor' => $porAsesor, 'Por aseguradora' => $porAseguradora] as $titulo => $filas)
            <x-filament::section :compact="true" :heading="$titulo">
                @forelse ($filas as $f)
                    <div style="display:flex; justify-content:space-between; font-size:14px; padding:4px 0; border-bottom:1px solid var(--gray-100)">
                        <span>{{ $f['nombre'] }} <span style="color:var(--gray-500)">({{ $f['n'] }})</span></span>
                        <strong>{{ $f['nps'] ?? '—' }}</strong>
                    </div>
                @empty
                    <p style="font-size:14px; color:var(--gray-500)">Sin respuestas en el periodo.</p>
                @endforelse
            </x-filament::section>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
