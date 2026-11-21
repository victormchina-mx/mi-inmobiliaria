@php
    use App\Models\Factura;
    $mx = fn ($n) => '$'.number_format((float) $n, 2);
    $t = config('tarzo.taller');
    $titulo = $f->etiquetaTipo();
    $metodos = ['PUE' => 'PUE · Pago en una sola exhibición', 'PPD' => 'PPD · Pago en parcialidades o diferido'];
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $titulo }} {{ $f->folio_completo }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a2124; }
    h1 { font-size: 15px; margin: 0; }
    h2 { font-size: 10px; margin: 12px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #0d7a4a; color: #0d7a4a; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 3px 5px; vertical-align: top; text-align: left; }
    .grid td, .grid th { border: 1px solid #d6dde0; }
    .grid th { background: #f2f4f5; font-size: 8px; text-transform: uppercase; color: #5a666c; }
    .num { text-align: right; white-space: nowrap; }
    .muted { color: #5a666c; }
    .tot .fuerte td { font-weight: bold; font-size: 11px; border-top: 1px solid #1a2124; }
    .sello { font-family: DejaVu Sans Mono, monospace; font-size: 6.5px; word-break: break-all; color: #3b4448; }
    .marca { color: #b42318; font-weight: bold; font-size: 11px; border: 2px solid #b42318; padding: 4px; text-align: center; margin-bottom: 8px; }
</style>
</head>
<body>
@if ($simulado)
    <div class="marca">DOCUMENTO DE PRUEBA · SIN VALIDEZ FISCAL (timbrado simulado)</div>
@endif
<table>
    <tr>
        <td style="width:60%">
            <h1>{{ $emisor['nombre'] }}</h1>
            <div>RFC {{ $emisor['rfc'] }} · Régimen {{ $emisor['regimen'] }}</div>
            <div class="muted">{{ $t['domicilio'] }}@if ($t['telefono']) · Tel. {{ $t['telefono'] }}@endif</div>
            <div class="muted">Lugar de expedición: {{ $emisor['codigo_postal'] }}</div>
        </td>
        <td class="num">
            <h1>{{ $titulo }}</h1>
            <div style="font-size:12px"><strong>{{ $f->folio_completo }}</strong></div>
            <div>Tipo {{ $f->tipo }} · CFDI 4.0</div>
            <div class="muted">{{ ($f->timbrada_at ?? now())->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

<h2>Receptor</h2>
<table>
    <tr>
        <td style="width:60%"><strong>{{ $f->receptor_nombre }}</strong><br>RFC {{ $f->receptor_rfc }} · Régimen {{ $f->receptor_regimen }} · C.P. {{ $f->receptor_cp }}</td>
        <td>Uso CFDI: {{ $f->uso_cfdi }}<br>
            @if ($f->tipo !== Factura::PAGO)
                Método: {{ $metodos[$f->metodo_pago] ?? $f->metodo_pago }}<br>Forma: {{ $f->forma_pago?->getLabel() }}
            @endif
        </td>
    </tr>
</table>
@if ($f->orden)
    <p class="muted">Orden {{ $f->orden->folio }} · {{ $f->orden->vehiculo->marca }} {{ $f->orden->vehiculo->modelo }} {{ $f->orden->vehiculo->anio }} · Placas {{ $f->orden->vehiculo->placas ?: '—' }} · VIN {{ $f->orden->vehiculo->vin ?: '—' }}@if ($f->orden->numero_siniestro) · Siniestro {{ $f->orden->numero_siniestro }}@endif</p>
@endif
@if ($f->relacionada)
    <p>CFDI relacionado (tipo {{ $f->relacion_tipo }}): {{ $f->relacionada->uuid }} · {{ $f->relacionada->folio_completo }}</p>
@endif

@if ($f->tipo === Factura::PAGO && $f->pago)
    <h2>Pago recibido</h2>
    <p>Fecha {{ $f->pago->fecha->format('d/m/Y') }} · {{ $f->pago->forma_pago->getLabel() }} · Monto {{ $mx($f->pago->aplicaciones->sum('monto')) }}</p>
    <table class="grid">
        <tr><th>Documento</th><th>UUID</th><th>Parcialidad</th><th class="num">Saldo anterior</th><th class="num">Pagado</th><th class="num">Saldo insoluto</th></tr>
        @foreach ($f->pago->aplicaciones as $a)
            <tr><td>{{ $a->factura->folio_completo }}</td><td>{{ $a->factura->uuid }}</td><td>{{ $a->parcialidad }}</td>
                <td class="num">{{ $mx($a->saldo_anterior) }}</td><td class="num">{{ $mx($a->monto) }}</td><td class="num">{{ $mx($a->saldo_anterior - $a->monto) }}</td></tr>
        @endforeach
    </table>
@else
    <h2>Conceptos</h2>
    <table class="grid">
        <tr><th>Clave SAT</th><th>Cant.</th><th>Unidad</th><th>Descripción</th><th class="num">Valor unitario</th><th class="num">Descuento</th><th class="num">Importe</th></tr>
        @foreach ($f->conceptos as $c)
            <tr>
                <td>{{ $c->clave_prod_serv }}</td>
                <td class="num">{{ rtrim(rtrim(number_format($c->cantidad, 3), '0'), '.') }}</td>
                <td>{{ $c->clave_unidad }} {{ $c->unidad }}</td>
                <td>{{ $c->descripcion }}@if ($c->no_identificacion)<br><span class="muted">No. parte {{ $c->no_identificacion }}</span>@endif</td>
                <td class="num">{{ $mx($c->valor_unitario) }}</td>
                <td class="num">{{ $c->descuento > 0 ? $mx($c->descuento) : '' }}</td>
                <td class="num">{{ $mx($c->importe) }}</td>
            </tr>
        @endforeach
    </table>
    <table class="tot" style="width:40%; margin-left:60%; margin-top:6px">
        <tr><td>Subtotal</td><td class="num">{{ $mx($f->subtotal) }}</td></tr>
        @if ($f->descuento > 0)<tr><td>Descuento</td><td class="num">−{{ $mx($f->descuento) }}</td></tr>@endif
        <tr><td>IVA 16 %</td><td class="num">{{ $mx($f->iva) }}</td></tr>
        <tr class="fuerte"><td>Total</td><td class="num">{{ $mx($f->total) }}</td></tr>
    </table>
@endif

@if ($f->notas)
    <p class="muted">{{ $f->notas }}</p>
@endif

<h2>Timbre fiscal</h2>
<table>
    <tr>
        <td style="width:110px">@if ($qr)<img src="{{ $qr }}" style="width:100px; height:100px">@endif</td>
        <td>
            <div>Folio fiscal (UUID): <strong>{{ $f->uuid }}</strong></div>
            <div>Fecha de timbrado: {{ $tfd['FechaTimbrado'] ?? '—' }} · Certificado SAT: {{ $tfd['NoCertificadoSAT'] ?? '—' }} · RFC PAC: {{ $tfd['RfcProvCertif'] ?? '—' }}</div>
            <div style="margin-top:4px">Sello digital del CFDI:</div><div class="sello">{{ $tfd['SelloCFD'] ?? '—' }}</div>
            <div style="margin-top:4px">Sello del SAT:</div><div class="sello">{{ $tfd['SelloSAT'] ?? '—' }}</div>
            <p class="muted" style="margin-top:6px">Este documento es una representación impresa de un CFDI.</p>
        </td>
    </tr>
</table>
</body>
</html>
