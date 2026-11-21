@php
    $mias = $this->getMias();
    $disponibles = $this->getDisponibles();
    $hoy = $this->getTerminadasHoy();
@endphp
<x-filament-panels::page>
    <div wire:poll.30s style="display:grid; gap:16px; max-width:640px;">
        @if ($hoy->isNotEmpty())
            <p style="font-size:14px; color:var(--gray-500)">Hoy terminaste {{ $hoy->count() }} {{ $hoy->count() === 1 ? 'tarea' : 'tareas' }}.</p>
        @endif

        @forelse ($mias as $t)
            @php $enProceso = $t->estado === \App\Enums\EstadoTarea::EnProceso; @endphp
            <x-filament::section :compact="true" wire:key="t-{{ $t->id }}">
                <div style="display:grid; gap:8px;">
                    <div style="display:flex; justify-content:space-between; gap:8px; align-items:baseline;">
                        <strong style="font-size:16px">{{ $t->descripcion }}</strong>
                        <x-filament::badge :color="$t->estado->getColor()">{{ $t->estado->getLabel() }}</x-filament::badge>
                    </div>
                    <div style="font-size:14px; color:var(--gray-600)">
                        {{ $t->orden->folio }} · {{ $t->orden->vehiculo->marca }} {{ $t->orden->vehiculo->modelo }}@if ($t->orden->vehiculo->placas) · {{ $t->orden->vehiculo->placas }}@endif
                        @if ($t->bahia)<br>{{ $t->bahia->nombre }}@endif
                        @if ($t->programada_para)<br>Programada: {{ $t->programada_para->translatedFormat('l j M') }}@endif
                    </div>
                    <div style="font-size:13px; color:var(--gray-500)">
                        Estándar {{ $t->horas_estandar }} h · llevas {{ number_format($t->horasAcumuladas(), 1) }} h
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px;">
                        @if ($enProceso)
                            <x-filament::button color="warning" size="lg" wire:click="mountAction('pausar', { tarea: {{ $t->id }} })">Pausar</x-filament::button>
                            <x-filament::button color="success" size="lg" wire:click="terminar({{ $t->id }})" wire:confirm="¿Terminaste esta tarea?">Terminar</x-filament::button>
                        @else
                            <x-filament::button size="lg" wire:click="iniciar({{ $t->id }})" style="grid-column: span 2">{{ $t->estado === \App\Enums\EstadoTarea::Pausada ? 'Reanudar' : 'Iniciar' }}</x-filament::button>
                        @endif
                        <x-filament::button color="gray" size="lg" icon="heroicon-o-camera" wire:click="mountAction('evidencia', { tarea: {{ $t->id }} })">Fotos</x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p>No tienes tareas asignadas.@if ($disponibles->isNotEmpty()) Toma una de las disponibles de tu área.@endif</p>
            </x-filament::section>
        @endforelse

        @if ($disponibles->isNotEmpty())
            <h2 style="font-weight:600; margin-top:8px;">Disponibles en tu área</h2>
            @foreach ($disponibles as $t)
                <x-filament::section :compact="true" wire:key="d-{{ $t->id }}">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
                        <div>
                            <strong>{{ $t->descripcion }}</strong><br>
                            <span style="font-size:13px; color:var(--gray-500)">{{ $t->orden->folio }} · {{ $t->orden->vehiculo->modelo }} · {{ $t->horas_estandar }} h</span>
                        </div>
                        <x-filament::button wire:click="iniciar({{ $t->id }})">Tomar e iniciar</x-filament::button>
                    </div>
                </x-filament::section>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
