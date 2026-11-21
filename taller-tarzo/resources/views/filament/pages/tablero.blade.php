@php
    $columnas = $this->getColumnas();
    $opciones = $this->getOpcionesFiltro();
    $puedeMover = $this->puedeMover();
    $colores = ['ok' => 'var(--success-500)', 'cerca' => 'var(--warning-500)', 'excedido' => 'var(--danger-500)'];
@endphp
<x-filament-panels::page>
    <div wire:poll.{{ $this->intervaloRefresco() }} style="display:grid; gap:16px;">
        {{-- Filtros --}}
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
            <x-filament::input.wrapper style="min-width:200px">
                <x-filament::input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Folio, placas o cliente" />
            </x-filament::input.wrapper>
            @foreach (['asesor' => ['asesores', 'Asesor'], 'aseguradora' => ['aseguradoras', 'Aseguradora'], 'tipo' => ['tipos', 'Tipo'], 'tecnico' => ['tecnicos', 'Técnico']] as $prop => [$lista, $etiqueta])
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="{{ $prop }}" aria-label="{{ $etiqueta }}">
                        <option value="">{{ $etiqueta }}: todos</option>
                        @foreach ($opciones[$lista] as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            @endforeach
            <x-filament::link tag="button" wire:click="limpiarFiltros" color="gray" size="sm">Limpiar</x-filament::link>
            <span style="margin-left:auto; font-size:12px; color:var(--gray-500)">
                Se actualiza solo · {{ $columnas->sum(fn ($c) => $c['ordenes']->count()) }} autos en taller
            </span>
        </div>

        {{-- Columnas --}}
        <div style="overflow-x:auto; padding-bottom:8px;">
            <div style="display:grid; grid-auto-flow:column; grid-auto-columns:minmax(220px, 1fr); gap:10px; min-width:min-content;">
                @foreach ($columnas as $col)
                    @php $etapa = $col['etapa']; @endphp
                    <section
                        x-data="{ sobre: false, puede: @js($puedeMover) }"
                        x-on:dragover.prevent="if (puede) sobre = true"
                        x-on:dragleave="sobre = false"
                        x-on:drop.prevent="sobre = false; if (puede) $wire.moverOrden(parseInt($event.dataTransfer.getData('orden')), {{ $etapa->id }})"
                        :style="sobre ? 'outline:2px dashed var(--primary-500)' : ''"
                        style="background:var(--gray-100); border-radius:10px; padding:8px; display:flex; flex-direction:column; gap:8px; min-height:200px;"
                        class="dark:bg-white/5"
                        aria-label="{{ $etapa->nombre }}"
                    >
                        <header style="display:flex; justify-content:space-between; align-items:center; padding:2px 4px;">
                            <h3 style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--gray-600)">{{ $etapa->nombre }}</h3>
                            <span style="font-size:12px; font-weight:700; padding:1px 8px; border-radius:999px; {{ $col['excedido'] ? 'background:var(--danger-100); color:var(--danger-700)' : 'background:var(--gray-200); color:var(--gray-700)' }}">
                                {{ $col['ordenes']->count() }}{{ $etapa->limite_autos ? '/'.$etapa->limite_autos : '' }}
                            </span>
                        </header>

                        @forelse ($col['ordenes'] as $orden)
                            @php
                                $semaforo = \App\Support\DatosTablero::semaforo($orden);
                                $avance = \App\Support\DatosTablero::avance($orden);
                                $url = $this->urlOrden($orden);
                            @endphp
                            <article
                                wire:key="orden-{{ $orden->id }}"
                                draggable="{{ $puedeMover ? 'true' : 'false' }}"
                                x-on:dragstart="$event.dataTransfer.setData('orden', '{{ $orden->id }}')"
                                style="background:white; border-radius:8px; border:1px solid var(--gray-200); border-left:4px solid {{ $colores[$semaforo] }}; padding:8px 10px; display:grid; gap:3px; font-size:13px; cursor:{{ $puedeMover ? 'grab' : 'default' }};"
                                class="dark:bg-gray-900 dark:border-white/10"
                            >
                                <div style="display:flex; justify-content:space-between; gap:6px; align-items:baseline;">
                                    @if ($url)
                                        <a href="{{ $url }}" style="font-weight:700; font-family:ui-monospace,monospace; font-size:12px;">{{ $orden->folio }}</a>
                                    @else
                                        <span style="font-weight:700; font-family:ui-monospace,monospace; font-size:12px;">{{ $orden->folio }}</span>
                                    @endif
                                    <span style="font-size:11px; color:{{ $colores[$semaforo] }}; font-weight:600;" title="Días en esta etapa">{{ $orden->diasEnEtapa() }} d</span>
                                </div>
                                <div style="font-weight:600;">{{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }}@if ($orden->vehiculo->placas) · {{ $orden->vehiculo->placas }}@endif</div>
                                <div style="color:var(--gray-500); font-size:12px;">{{ $orden->cliente->nombre_completo }} · {{ \App\Support\DatosTablero::etiquetaTipo($orden) }}</div>
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:6px; font-size:11px; color:var(--gray-500);">
                                    <span>
                                        @if ($avance['total'])
                                            {{ $avance['hechas'] }}/{{ $avance['total'] }} tareas
                                            @if ($avance['tecnicos']) · {{ implode(' ', $avance['tecnicos']) }}@endif
                                        @else
                                            Sin tareas
                                        @endif
                                    </span>
                                    @if ($orden->fecha_promesa)
                                        <span style="{{ \App\Support\DatosTablero::promesaVencida($orden) ? 'color:var(--danger-600); font-weight:600' : '' }}" title="Fecha promesa">{{ $orden->fecha_promesa->format('d/m') }}</span>
                                    @endif
                                </div>
                                @if ($puedeMover)
                                    <button type="button" wire:click="mountAction('mover', { orden: {{ $orden->id }} })" style="justify-self:start; font-size:11px; color:var(--primary-600); font-weight:600;">Mover…</button>
                                @endif
                            </article>
                        @empty
                            <p style="font-size:12px; color:var(--gray-400); text-align:center; padding:12px 0;">Sin autos</p>
                        @endforelse
                    </section>
                @endforeach
            </div>
        </div>

        <p style="font-size:12px; color:var(--gray-500)">
            Borde verde: dentro de tiempo · ámbar: cerca del límite · rojo: excedió los días de la etapa.
            @if ($puedeMover) Arrastra una tarjeta a otra columna o usa "Mover…". @endif
        </p>
    </div>
</x-filament-panels::page>
