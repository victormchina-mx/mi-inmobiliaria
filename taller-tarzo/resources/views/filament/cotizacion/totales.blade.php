@php $mx = fn ($n) => '$'.number_format((float) $n, 2); @endphp
@if ($t)
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:24px; font-variant-numeric: tabular-nums;">
    <dl style="display:grid; grid-template-columns: 1fr auto; gap:6px 16px; margin:0;">
        @if ($t['subtotal_hojalateria_pintura'])<dt>Hojalatería y pintura</dt><dd style="margin:0; text-align:right">{{ $mx($t['subtotal_hojalateria_pintura']) }}</dd>@endif
        @if ($t['subtotal_mano_obra'])<dt>Mano de obra</dt><dd style="margin:0; text-align:right">{{ $mx($t['subtotal_mano_obra']) }}</dd>@endif
        @if ($t['subtotal_refacciones'])<dt>Refacciones</dt><dd style="margin:0; text-align:right">{{ $mx($t['subtotal_refacciones']) }}</dd>@endif
        @if ($t['subtotal_otros'])<dt>Otros</dt><dd style="margin:0; text-align:right">{{ $mx($t['subtotal_otros']) }}</dd>@endif
        @if ($t['ciclos_cabina'])<dt>Cabina ({{ $t['ciclos_cabina'] }} {{ $t['ciclos_cabina'] === 1 ? 'ciclo' : 'ciclos' }})</dt><dd style="margin:0; text-align:right">{{ $mx($t['importe_cabina']) }}</dd>@endif
        <dt>Subtotal</dt><dd style="margin:0; text-align:right">{{ $mx($t['subtotal']) }}</dd>
        <dt>IVA</dt><dd style="margin:0; text-align:right">{{ $mx($t['iva']) }}</dd>
        <dt style="font-weight:700; font-size:18px; border-top:1px solid var(--gray-300); padding-top:6px">Total</dt>
        <dd style="margin:0; text-align:right; font-weight:700; font-size:18px; border-top:1px solid var(--gray-300); padding-top:6px">{{ $mx($t['total']) }}</dd>
    </dl>
    @if ($seguro)
    <dl style="display:grid; grid-template-columns: 1fr auto; gap:6px 16px; margin:0; background:var(--gray-50); border-radius:8px; padding:12px 16px;">
        <dt>Deducible</dt><dd style="margin:0; text-align:right">{{ $mx($t['deducible']) }}</dd>
        @if ($contexto->demerito)<dt>Demérito</dt><dd style="margin:0; text-align:right">{{ $mx($contexto->demerito) }}</dd>@endif
        @if ($contexto->salvamento)<dt>Salvamento</dt><dd style="margin:0; text-align:right">−{{ $mx($contexto->salvamento) }}</dd>@endif
        <dt style="font-weight:700; color:var(--primary-600)">Paga la aseguradora</dt><dd style="margin:0; text-align:right; font-weight:700; color:var(--primary-600)">{{ $mx($t['monto_aseguradora']) }}</dd>
        <dt style="font-weight:700">Paga el cliente</dt><dd style="margin:0; text-align:right; font-weight:700">{{ $mx($t['monto_cliente']) }}</dd>
        @unless ($siniestroCapturado)
            <dd style="grid-column:1/-1; margin:0; color:var(--warning-600); font-size:13px">Falta capturar el siniestro (deducible, demérito, salvamento) en la orden.</dd>
        @endunless
    </dl>
    @endif
</div>
@endif
