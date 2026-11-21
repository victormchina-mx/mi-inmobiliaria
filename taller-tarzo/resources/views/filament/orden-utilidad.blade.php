@php
    $mx = fn ($n) => '$'.number_format((float) $n, 2);
    $color = $u['margen'] === null ? 'var(--gray-500)' : ($u['margen'] < $minimo ? 'var(--danger-600)' : 'var(--success-600)');
    $filas = [
        'Refacciones (costo real del proveedor)' => $u['costo_refacciones'],
        'Materiales y consumibles de almacén' => $u['costo_materiales'],
        'Mano de obra ('.number_format($u['horas_reales'], 1).' h reales de '.number_format($u['horas_estandar'], 1).' h estándar)' => $u['costo_mano_obra'],
        'Subcontratos y gastos' => $u['costo_subcontratos'],
    ];
@endphp
<div style="display:grid; gap:10px; font-size:14px;">
    <div style="display:flex; justify-content:space-between;">
        <span>Ingreso {{ $u['ingreso_estimado'] ? '(estimado: aún sin facturar)' : 'facturado' }}</span><strong>{{ $mx($u['ingreso']) }}</strong>
    </div>
    @foreach ($filas as $concepto => $monto)
        <div style="display:flex; justify-content:space-between; color:var(--gray-600)">
            <span>− {{ $concepto }}</span><span>{{ $mx($monto) }}</span>
        </div>
    @endforeach
    <div style="display:flex; justify-content:space-between; border-top:1px solid var(--gray-300); padding-top:8px; font-size:16px;">
        <strong>Utilidad</strong><strong style="color:{{ $color }}">{{ $mx($u['utilidad']) }} · {{ $u['margen'] === null ? '—' : number_format($u['margen'], 1).' %' }}</strong>
    </div>
    <p style="font-size:12px; color:var(--gray-500)">
        Importes sin IVA. Margen mínimo configurado: {{ $minimo }} %.
        @if ($cierre) Último cálculo guardado: {{ $cierre->calculado_at->format('d/m/Y H:i') }}. @endif
        La mano de obra usa el costo de hora-hombre de cada técnico (Catálogos → Técnicos).
    </p>
</div>
