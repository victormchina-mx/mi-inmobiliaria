<x-filament-panels::page>
    {{ $this->table }}

    <x-filament::section heading="Agenda de cabina de pintura" description="Tareas asignadas a una cabina en los próximos 7 días. Programa la fecha desde las tareas de cada orden.">
        @php $agenda = $this->getAgendaCabina(); @endphp
        @if ($agenda->isEmpty())
            <p style="color:var(--gray-500)">No hay tareas asignadas a cabina. Asigna la bahía tipo "Cabina de pintura" en las tareas de pintura.</p>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px;">
                @foreach ($agenda as $dia => $tareas)
                    <div style="border:1px solid var(--gray-200); border-radius:8px; padding:10px 12px; display:grid; gap:6px;" class="dark:border-white/10">
                        <strong style="text-transform:capitalize">{{ $dia }}</strong>
                        <span style="font-size:12px; color:var(--gray-500)">{{ rtrim(rtrim(number_format($tareas->sum('horas_estandar'), 1), '0'), '.') }} h programadas</span>
                        @foreach ($tareas as $t)
                            <div style="font-size:13px; border-top:1px solid var(--gray-100); padding-top:6px;">
                                <b>{{ $t->orden->folio }}</b> · {{ $t->bahia->nombre }}<br>
                                {{ $t->descripcion }} · {{ $t->horas_estandar }} h
                                @if ($t->tecnico)<br><span style="color:var(--gray-500)">{{ $t->tecnico->nombre }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
