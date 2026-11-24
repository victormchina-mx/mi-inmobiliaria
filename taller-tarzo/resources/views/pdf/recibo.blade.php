@php
    $mx = fn ($n) => '$'.number_format((float) $n, 2);
    $t = config('tarzo.taller');
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Recibo {{ $pago->folio }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a2124; }
    h1 { font-size: 16px; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 4px 6px; text-align: left; vertical-align: top; }
    .grid td, .grid th { border: 1px solid #d6dde0; }
    .num { text-align: right; }
    .muted { color: #5a666c; }
    .monto { font-size: 20px; font-weight: bold; color: #1f3e7f; }
</style>
</head>
<body>
<table>
    <tr>
        <td><h1>{{ $t['razon_social'] }}</h1><div class="muted">{{ $t['domicilio'] }}@if ($t['telefono']) · Tel. {{ $t['telefono'] }}@endif</div></td>
        <td class="num"><h1>Recibo de pago</h1><strong>{{ $pago->folio }}</strong><div class="muted">{{ $pago->fecha->format('d/m/Y H:i') }}</div></td>
    </tr>
</table>
@if ($pago->cancelado_at)
    <p style="color:#b42318; font-weight:bold">CANCELADO el {{ $pago->cancelado_at->format('d/m/Y') }}: {{ $pago->motivo_cancelacion }}</p>
@endif
<p style="margin-top:16px">Recibimos de <strong>{{ $pago->pagador() }}</strong> la cantidad de</p>
<p class="monto">{{ $mx($pago->monto) }} MXN</p>
<p>Forma de pago: {{ $pago->forma_pago->getLabel() }}@if ($pago->referencia) · Referencia {{ $pago->referencia }}@endif</p>
@if ($pago->orden)
    <p>Orden {{ $pago->orden->folio }} · {{ $pago->orden->vehiculo->marca }} {{ $pago->orden->vehiculo->modelo }} {{ $pago->orden->vehiculo->placas }}</p>
@endif
@if ($pago->aplicaciones->isNotEmpty())
    <table class="grid" style="margin-top:8px">
        <tr><th>Factura</th><th class="num">Saldo anterior</th><th class="num">Aplicado</th><th class="num">Saldo</th></tr>
        @foreach ($pago->aplicaciones as $a)
            <tr><td>{{ $a->factura->folio_completo }}</td><td class="num">{{ $mx($a->saldo_anterior) }}</td><td class="num">{{ $mx($a->monto) }}</td><td class="num">{{ $mx($a->saldo_anterior - $a->monto) }}</td></tr>
        @endforeach
    </table>
@endif
@if ($pago->por_aplicar > 0.009)
    <p><strong>{{ $pago->es_anticipo ? 'Anticipo' : 'Saldo a favor' }}:</strong> {{ $mx($pago->por_aplicar) }} (se aplicará a la factura de la orden).</p>
@endif
@if ($pago->notas)<p class="muted">{{ $pago->notas }}</p>@endif
<p class="muted" style="margin-top:30px">Recibió: {{ $pago->cajero?->name }} · Este recibo no es un comprobante fiscal.</p>
</body>
</html>
