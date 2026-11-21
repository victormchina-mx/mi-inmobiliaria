<div style="max-width:160px; margin:0 auto;">
    {!! \App\Support\DiagramaDanos::svg($zonas ?? [], 160) !!}
    <p style="font-size:12px; color:var(--gray-500); text-align:center; margin-top:4px;">
        {{ count($zonas ?? []) }} {{ count($zonas ?? []) === 1 ? 'daño marcado' : 'daños marcados' }}
    </p>
</div>
