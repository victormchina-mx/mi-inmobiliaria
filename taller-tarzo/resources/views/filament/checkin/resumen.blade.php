@php
    /** @var \App\Models\OrdenServicio $orden */
    $orden = $getRecord();
    $checkin = $orden->checkin;
@endphp
<div class="fi-sc" style="display:grid; gap:12px;">
    @if (! $checkin)
        <p style="color:var(--gray-500)">Todavía no se hace el inventario de ingreso.</p>
    @else
        <div style="display:flex; gap:14px; align-items:flex-start;">
            <div style="flex:none">{!! \App\Support\DiagramaDanos::svg($checkin->danos->pluck('zona'), 80) !!}</div>
            <div style="display:grid; gap:6px; font-size:14px;">
                <div><strong>{{ $checkin->danos->count() }}</strong> {{ $checkin->danos->count() === 1 ? 'daño previo' : 'daños previos' }}</div>
                <div><strong>{{ count($checkin->fotos ?? []) }}</strong> {{ count($checkin->fotos ?? []) === 1 ? 'foto' : 'fotos' }} @if($checkin->video) · video @endif</div>
                <div>Gasolina: <strong>{{ $checkin->nivel_combustible !== null ? $checkin->nivel_combustible.'/8' : '—' }}</strong></div>
                @if ($checkin->testigosLegibles())
                    <div style="color:var(--danger-600)">Testigos: {{ implode(', ', $checkin->testigosLegibles()) }}</div>
                @endif
            </div>
        </div>
        @if ($checkin->estaFirmado())
            <div style="color:var(--success-600); font-size:14px;">
                Firmado por <strong>{{ $checkin->firmado_por }}</strong><br>
                {{ $checkin->firmado_at->format('d/m/Y H:i') }} · {{ $checkin->firma_medio === 'enlace' ? 'por enlace' : 'en tableta' }}
            </div>
            @unless ($checkin->huellaVigente())
                <div style="color:var(--danger-600); font-size:13px;">El inventario cambió después de firmado. La firma ya no corresponde al contenido actual.</div>
            @endunless
        @else
            <div style="color:var(--warning-600); font-size:14px;">Pendiente de firma del cliente.</div>
        @endif
    @endif
</div>
