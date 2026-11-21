@php
    use App\Support\Media;
    use App\Support\DiagramaDanos;
    $t = config('tarzo.taller');
    $v = $orden->vehiculo;
    $c = $orden->cliente;
    $fotos = collect($checkin->fotos ?? [])->take(6)->map(fn ($f) => Media::dataUri($f))->filter();
    $fotosDanos = $checkin->danos->filter(fn ($d) => $d->foto)->take(8);
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Inventario de ingreso {{ $orden->folio }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a2124; }
    h1 { font-size: 16px; margin: 0; }
    h2 { font-size: 11px; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #0d7a4a; color: #0d7a4a; text-transform: uppercase; letter-spacing: .5px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 3px 5px; vertical-align: top; text-align: left; }
    .grid td { border: 1px solid #d6dde0; }
    .etq { color: #5a666c; width: 22%; }
    .muted { color: #5a666c; }
    .chip { display: inline-block; border: 1px solid #c3ccd0; border-radius: 8px; padding: 1px 6px; margin: 0 3px 3px 0; }
    .falta { border-color: #e5a29c; color: #b42318; }
    .foto { width: 31%; display: inline-block; margin: 0 1% 6px 0; }
    .foto img { width: 100%; height: 110px; }
    .firma img { height: 70px; }
    .pie { font-size: 8px; color: #5a666c; margin-top: 10px; }
    .legal { font-size: 8px; color: #3f4a50; }
    .legal ol { margin: 0; padding-left: 14px; }
</style>
</head>
<body>
<table>
    <tr>
        <td>
            <h1>{{ $t['razon_social'] }}</h1>
            @if($t['domicilio'])<div class="muted">{{ $t['domicilio'] }}</div>@endif
            @if($t['telefono'])<div class="muted">Tel. {{ $t['telefono'] }} @if($t['email']) · {{ $t['email'] }}@endif</div>@endif
        </td>
        <td style="text-align:right">
            <h1>Inventario de ingreso</h1>
            <div><strong>{{ $orden->folio }}</strong> · {{ $orden->tipo->getLabel() }}</div>
            <div class="muted">{{ $orden->fecha_ingreso->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

<h2>Cliente y vehículo</h2>
<table class="grid">
    <tr><td class="etq">Cliente</td><td>{{ $c->nombre_completo }}</td><td class="etq">Teléfono</td><td>{{ $c->telefono ?? '—' }}</td></tr>
    <tr><td class="etq">Vehículo</td><td>{{ $v->marca }} {{ $v->modelo }} {{ $v->anio }} {{ $v->version }}</td><td class="etq">Color</td><td>{{ $v->color ?? '—' }}</td></tr>
    <tr><td class="etq">Placas</td><td>{{ $v->placas ?? '—' }}</td><td class="etq">VIN</td><td>{{ $v->vin ?? '—' }}</td></tr>
    <tr><td class="etq">Kilometraje</td><td>{{ $checkin->kilometraje ? number_format($checkin->kilometraje).' km' : '—' }}</td><td class="etq">Gasolina</td><td>{{ $checkin->nivel_combustible !== null ? $checkin->nivel_combustible.'/8' : '—' }}</td></tr>
    @if($orden->aseguradora)
    <tr><td class="etq">Aseguradora</td><td>{{ $orden->aseguradora->nombre }}</td><td class="etq">Siniestro</td><td>{{ $orden->numero_siniestro ?? '—' }}</td></tr>
    @endif
    <tr><td class="etq">Motivo de ingreso</td><td colspan="3">{{ $orden->motivo_ingreso ?? '—' }}</td></tr>
</table>

<h2>Tablero e inventario</h2>
<p><strong>Testigos encendidos:</strong>
    @forelse($checkin->testigosLegibles() as $x)<span class="chip falta">{{ $x }}</span>@empty Ninguno @endforelse
</p>
<p><strong>Se recibe con:</strong>
    @forelse($checkin->inventarioLegible() as $x)<span class="chip">{{ $x }}</span>@empty — @endforelse
</p>
<p><strong>No se recibe:</strong>
    @forelse($checkin->inventarioFaltante() as $x)<span class="chip falta">{{ $x }}</span>@empty — @endforelse
</p>
<p><strong>Pertenencias declaradas:</strong> {{ $checkin->pertenencias ?: 'Ninguna' }}</p>
@if($checkin->observaciones)<p><strong>Observaciones:</strong> {{ $checkin->observaciones }}</p>@endif

<h2>Daños previos</h2>
<table>
    <tr>
        <td style="width:110px"><img src="{{ DiagramaDanos::dataUri($checkin->danos->pluck('zona'), 100) }}" width="100" height="200" alt=""></td>
        <td>
            @if($checkin->danos->isEmpty())
                Sin daños previos registrados.
            @else
            <table class="grid">
                <tr><th>Zona</th><th>Tipo</th><th>Descripción</th></tr>
                @foreach($checkin->danos as $d)
                    <tr><td>{{ $d->zona->getLabel() }}</td><td>{{ $d->tipo->getLabel() }}</td><td>{{ $d->descripcion }}</td></tr>
                @endforeach
            </table>
            @endif
        </td>
    </tr>
</table>
@if($fotosDanos->isNotEmpty())
    <div>
    @foreach($fotosDanos as $d)
        @if($uri = Media::dataUri($d->foto))
            <div class="foto"><img src="{{ $uri }}" alt=""><div class="muted">{{ $d->zona->getLabel() }} · {{ $d->tipo->getLabel() }}</div></div>
        @endif
    @endforeach
    </div>
@endif

@if($fotos->isNotEmpty())
<h2>Fotografías de ingreso</h2>
<div>@foreach($fotos as $uri)<div class="foto"><img src="{{ $uri }}" alt=""></div>@endforeach</div>
@endif

<h2>Condiciones</h2>
<div class="legal">@include('legal.condiciones')</div>

<h2>Conformidad del cliente</h2>
@if($checkin->estaFirmado())
<table>
    <tr>
        <td class="firma" style="width:45%">@if($f = Media::dataUri($checkin->firma))<img src="{{ $f }}" alt="Firma">@endif
            <div style="border-top:1px solid #1a2124; padding-top:2px">{{ $checkin->firmado_por }}</div></td>
        <td class="muted">
            Firmado el {{ $checkin->firmado_at->format('d/m/Y H:i') }} ({{ $checkin->firma_medio === 'enlace' ? 'desde enlace enviado al cliente' : 'en tableta del taller' }}).<br>
            Aceptó condiciones del servicio y aviso de privacidad.<br>
            @if($checkin->firma_ip) IP {{ $checkin->firma_ip }}.<br>@endif
            Huella del contenido: {{ substr($checkin->firma_huella, 0, 16) }}…
        </td>
    </tr>
</table>
@else
<p class="falta"><strong>Pendiente de firma del cliente.</strong></p>
@endif

<p class="pie">Recibió: {{ $orden->asesor?->name ?? '—' }} · Aviso de privacidad completo en {{ route('aviso-privacidad') }}</p>
</body>
</html>
