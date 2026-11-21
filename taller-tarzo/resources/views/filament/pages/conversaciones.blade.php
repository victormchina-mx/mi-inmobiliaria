@php
    $bandeja = $this->getBandeja();
    $actual = $this->getClienteActual();
    $hilo = $this->getHilo();
    $puede = $this->puedeResponder();
@endphp
<x-filament-panels::page>
    <div wire:poll.20s style="display:grid; grid-template-columns:minmax(220px, 320px) 1fr; gap:16px; align-items:start;" class="tarzo-chat">
        <x-filament::section :compact="true">
            <x-filament::input.wrapper>
                <x-filament::input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar cliente…" />
            </x-filament::input.wrapper>
            <div style="display:grid; gap:2px; margin-top:8px; max-height:65vh; overflow-y:auto;">
                @forelse ($bandeja as $m)
                    <button type="button" wire:key="b-{{ $m->cliente_id }}" wire:click="abrir({{ $m->cliente_id }})"
                        style="text-align:left; padding:8px; border-radius:8px; background:{{ $m->cliente_id === $cliente ? 'var(--primary-50, #fff7ed)' : 'transparent' }};">
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <strong style="font-size:14px">{{ $m->cliente?->nombre_completo ?? 'Desconocido' }}</strong>
                            <span style="font-size:12px; color:var(--gray-500)">{{ $m->created_at->diffForHumans(short: true) }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; gap:8px; font-size:13px; color:var(--gray-500)">
                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $m->esEntrante() ? '' : 'Tú: ' }}{{ \Illuminate\Support\Str::limit($m->cuerpo ?: $m->evento?->getLabel(), 60) }}</span>
                            @if ($m->sin_leer)
                                <x-filament::badge color="success" size="sm">{{ $m->sin_leer }}</x-filament::badge>
                            @endif
                        </div>
                    </button>
                @empty
                    <p style="font-size:14px; color:var(--gray-500); padding:8px">Aún no hay conversaciones.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section :compact="true">
            @if ($actual)
                <x-slot name="heading">{{ $actual->nombre_completo }}</x-slot>
                <x-slot name="description">{{ $actual->numeroWhatsapp() ?? 'Sin WhatsApp' }} · <a href="{{ $this->urlCliente() }}" style="text-decoration:underline">ver ficha</a></x-slot>

                <div style="display:flex; flex-direction:column; gap:8px; max-height:55vh; overflow-y:auto; padding:4px;">
                    @foreach ($hilo as $m)
                        @php
                            $entrante = $m->esEntrante();
                            $fondo = $entrante ? 'var(--gray-100)' : 'var(--primary-100, #ffedd5)';
                            $alinear = $entrante ? 'flex-start' : 'flex-end';
                        @endphp
                        <div wire:key="m-{{ $m->id }}" style="align-self:{{ $alinear }}; max-width:78%; background:{{ $fondo }}; color:var(--gray-900); border-radius:12px; padding:8px 12px;">
                            @if (! $entrante && $m->evento && $m->evento->value !== 'respuesta')
                                <div style="font-size:11px; color:var(--gray-600); margin-bottom:2px">Automático · {{ $m->evento->getLabel() }}@if ($m->canal === 'correo') · correo @endif</div>
                            @endif
                            <div style="white-space:pre-line; font-size:14px">{{ $m->cuerpo }}</div>
                            <div style="font-size:11px; color:var(--gray-500); text-align:right; margin-top:2px">
                                {{ $m->created_at->format('d/m H:i') }}@if ($m->autor) · {{ $m->autor->name }}@endif
                                @if (! $entrante) · {{ $m->estado->getLabel() }}@endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="enviar" style="margin-top:12px; display:grid; gap:8px;">
                    @if ($puede)
                        <textarea wire:model="texto" rows="3" required maxlength="4000" placeholder="Escribe una respuesta…"
                            style="width:100%; border-radius:8px; border:1px solid var(--gray-300); padding:8px; background:transparent;"></textarea>
                        @error('texto') <p style="color:var(--danger-600); font-size:13px">{{ $message }}</p> @enderror
                        <div><x-filament::button type="submit" icon="heroicon-o-paper-airplane">Enviar por WhatsApp</x-filament::button></div>
                    @else
                        <p style="font-size:13px; color:var(--gray-500)">
                            Solo puedes responder con texto libre durante las 24 h siguientes al último mensaje del cliente
                            (regla de WhatsApp) y con WhatsApp configurado. Fuera de ese plazo, llámale o espera a que escriba.
                        </p>
                    @endif
                </form>
            @else
                <p style="color:var(--gray-500)">Elige una conversación.</p>
            @endif
        </x-filament::section>
    </div>
    <style>
        @media (max-width: 768px) { .tarzo-chat { grid-template-columns: 1fr !important; } }
    </style>
</x-filament-panels::page>
