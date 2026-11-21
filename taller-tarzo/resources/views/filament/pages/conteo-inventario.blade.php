<x-filament-panels::page>
    <div style="display:grid; gap:16px; max-width:900px;">
        <form wire:submit="agregar" style="display:flex; gap:8px;">
            <x-filament::input.wrapper style="flex:1">
                <x-filament::input type="text" wire:model="codigo" autofocus inputmode="text" autocomplete="off" placeholder="Escanea o escribe la clave o el código de barras y pulsa Enter" />
            </x-filament::input.wrapper>
            <x-filament::button type="submit">Agregar</x-filament::button>
        </form>
        <p style="font-size:13px; color:var(--gray-500)">Escanear dos veces el mismo producto suma una pieza. Corrige la cantidad si contaste por caja o litro.</p>

        @if ($conteo)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                    <thead>
                        <tr style="text-align:left; color:var(--gray-500); font-size:12px; text-transform:uppercase;">
                            <th style="padding:6px">Clave</th><th style="padding:6px">Producto</th>
                            <th style="padding:6px; text-align:right">Sistema</th><th style="padding:6px; text-align:right">Contado</th>
                            <th style="padding:6px; text-align:right">Diferencia</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conteo as $id => $fila)
                            @php $dif = is_numeric($fila['contado']) ? round((float) $fila['contado'] - $fila['sistema'], 3) : null; @endphp
                            <tr wire:key="c-{{ $id }}" style="border-top:1px solid var(--gray-200)">
                                <td style="padding:6px; font-family:ui-monospace,monospace">{{ $fila['sku'] }}</td>
                                <td style="padding:6px">{{ $fila['nombre'] }}</td>
                                <td style="padding:6px; text-align:right">{{ $fila['sistema'] }} {{ $fila['unidad'] }}</td>
                                <td style="padding:6px; text-align:right; width:120px">
                                    <x-filament::input.wrapper><x-filament::input type="number" step="0.001" min="0" wire:model.live.debounce.500ms="conteo.{{ $id }}.contado" /></x-filament::input.wrapper>
                                </td>
                                <td style="padding:6px; text-align:right; font-weight:600; color:{{ $dif === null || $dif == 0 ? 'var(--gray-500)' : ($dif > 0 ? 'var(--success-600)' : 'var(--danger-600)') }}">
                                    {{ $dif === null ? '—' : ($dif > 0 ? '+' : '').$dif }}
                                </td>
                                <td style="padding:6px"><x-filament::link tag="button" color="danger" wire:click="quitar({{ $id }})">Quitar</x-filament::link></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>
                <x-filament::button color="success" wire:click="aplicar" wire:confirm="Se registrarán ajustes para que la existencia del sistema sea igual a lo contado. ¿Continuar?">Aplicar ajustes</x-filament::button>
            </div>
        @else
            <x-filament::section><p style="color:var(--gray-500)">Todavía no hay productos en este conteo.</p></x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
