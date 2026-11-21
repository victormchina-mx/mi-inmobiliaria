@php
    use App\Enums\EstadoPartida;
    use App\Enums\Paga;
    use App\Enums\TipoPartida;
    use App\Support\Media;
    $t = config('tarzo.taller');
    $v = $orden->vehiculo;
    $seguro = $cotizacion->esSeguro();
    $valuacion = $formato === 'valuacion';
    $partidas = $valuacion ? $cotizacion->partidas->filter(fn ($p) => $p->paga === Paga::Aseguradora) : $cotizacion->partidas;
    $grupos = $partidas->groupBy(fn ($p) => $p->tipo->value);
    $mx = fn ($n) => '$'.number_format((float) $n, 2);
    $fotos = $cotizacion->partidas->pluck('diagnostico.fotos')->filter()->flatten()->take(6)->map(fn ($f) => Media::dataUri($f))->filter();
    $siniestro = $orden->siniestro;
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $valuacion ? 'Valuación' : 'Presupuesto' }} {{ $cotizacion->folio }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1a2124; }
    h1 { font-size: 15px; margin: 0; }
    h2 { font-size: 10.5px; margin: 12px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #0d7a4a; color: #0d7a4a; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 3px 5px; vertical-align: top; text-align: left; }
    .grid td, .grid th { border: 1px solid #d6dde0; }
    .grid th { background: #f2f4f5; font-size: 8.5px; text-transform: uppercase; color: #5a666c; }
    .num { text-align: right; white-space: nowrap; }
    .muted { color: #5a666c; }
    .rech td { color: #9aa4a9; text-decoration: line-through; }
    .tot td { padding: 2px 5px; }
    .tot .fuerte td { font-weight: bold; font-size: 11px; border-top: 1px solid #1a2124; }
    .foto { width: 31%; display: inline-block; margin: 0 1% 6px 0; }
    .foto img { width: 100%; height: 100px; }
    .badge { font-size: 8px; padding: 1px 4px; border-radius: 6px; border: 1px solid #c3ccd0; }
</style>
</head>
<body>
<table>
    <tr>
        <td>
            <h1>{{ $t['razon_social'] }}</h1>
            @if($t['domicilio'])<div class="muted">{{ $t['domicilio'] }}</div>@endif
            @if($t['telefono'])<div class="muted">Tel. {{ $t['telefono'] }}@if($t['email']) · {{ $t['email'] }}@endif</div>@endif
        </td>
        <td style="text-align:right">
            <h1>{{ $valuacion ? 'Valuación de daños' : 'Presupuesto' }}</h1>
            <div><strong>{{ $cotizacion->folio }}</strong> · versión {{ $cotizacion->version }}</div>
            <div class="muted">{{ $cotizacion->updated_at->format('d/m/Y') }} · vigencia {{ $cotizacion->vigencia_dias }} días</div>
            <div>{{ $cotizacion->estado->getLabel() }}</div>
        </td>
    </tr>
</table>

<h2>Cliente y vehículo</h2>
<table class="grid">
    <tr><td class="muted" style="width:18%">Cliente</td><td>{{ $orden->cliente->nombre_completo }}</td><td class="muted" style="width:18%">Orden</td><td>{{ $orden->folio }}</td></tr>
    <tr><td class="muted">Vehículo</td><td>{{ $v->marca }} {{ $v->modelo }} {{ $v->anio }} {{ $v->color }}</td><td class="muted">Placas / VIN</td><td>{{ $v->placas ?? '—' }} · {{ $v->vin ?? '—' }}</td></tr>
    @if($seguro)
    <tr><td class="muted">Aseguradora</td><td>{{ $cotizacion->aseguradora?->nombre }}</td><td class="muted">Siniestro</td><td>{{ $siniestro?->numero ?? $orden->numero_siniestro ?? '—' }}</td></tr>
    @if($siniestro?->ajustador_nombre)<tr><td class="muted">Ajustador</td><td colspan="3">{{ $siniestro->ajustador_nombre }}</td></tr>@endif
    @endif
</table>

@foreach([TipoPartida::HojalateriaPintura, TipoPartida::ManoObra, TipoPartida::Refaccion, TipoPartida::Otro] as $tipo)
    @php($lista = $grupos->get($tipo->value))
    @continue(! $lista)
    <h2>{{ $tipo->getLabel() }}</h2>
    <table class="grid">
        <tr>
            <th style="width:4%">#</th>
            <th>Concepto</th>
            @if($tipo === TipoPartida::HojalateriaPintura)
                <th>Operación</th><th class="num">Lam. h</th><th class="num">Pint. h</th><th class="num">Materiales</th>
            @elseif($tipo === TipoPartida::ManoObra)
                <th class="num">Horas</th><th class="num">Tarifa</th>
            @else
                <th>No. parte</th><th class="num">Cant.</th><th class="num">P. unitario</th>
            @endif
            @if($seguro && ! $valuacion)<th>Paga</th>@endif
            <th class="num">Importe</th>
            @unless($valuacion)<th>Estado</th>@endunless
        </tr>
        @foreach($lista as $i => $p)
            <tr class="{{ $p->estado === EstadoPartida::Rechazada ? 'rech' : '' }}">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->descripcion }}@if($p->calidad)<br><span class="muted">{{ $p->calidad->getLabel() }}</span>@endif</td>
                @if($tipo === TipoPartida::HojalateriaPintura)
                    <td>{{ $p->operacion_hyp?->getLabel() }}@if($p->nivel_dano) · daño {{ strtolower($p->nivel_dano->getLabel()) }}@endif @if($p->tipo_pintura)<br><span class="muted">{{ $p->tipo_pintura->getLabel() }}</span>@endif</td>
                    <td class="num">{{ $p->horas_laminado ?: '—' }}</td>
                    <td class="num">{{ $p->horas_pintura ?: '—' }}</td>
                    <td class="num">{{ $mx($p->importe_materiales) }}</td>
                @elseif($tipo === TipoPartida::ManoObra)
                    <td class="num">{{ $p->horas }}</td>
                    <td class="num">{{ $mx($p->tarifa_aplicada) }}</td>
                @else
                    <td>{{ $p->numero_parte ?? '—' }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($p->cantidad, 2), '0'), '.') }}</td>
                    <td class="num">{{ $mx($p->precio_unitario) }}</td>
                @endif
                @if($seguro && ! $valuacion)<td>{{ $p->paga->getLabel() }}</td>@endif
                <td class="num">{{ $mx($p->importe) }}</td>
                @unless($valuacion)<td><span class="badge">{{ $p->estado->getLabel() }}</span></td>@endunless
            </tr>
        @endforeach
    </table>
@endforeach

<h2>Totales</h2>
<table>
    <tr>
        <td style="width:55%" class="muted">
            @if($cotizacion->ciclos_cabina)Incluye {{ $cotizacion->ciclos_cabina }} {{ $cotizacion->ciclos_cabina === 1 ? 'ciclo' : 'ciclos' }} de cabina de pintura.<br>@endif
            Importes en pesos mexicanos. Las partidas rechazadas no suman.<br>
            @if($cotizacion->notas)<br><strong>Notas:</strong> {{ $cotizacion->notas }}@endif
        </td>
        <td>
            <table class="tot">
                @if($cotizacion->subtotal_hojalateria_pintura)<tr><td>Hojalatería y pintura</td><td class="num">{{ $mx($cotizacion->subtotal_hojalateria_pintura) }}</td></tr>@endif
                @if($cotizacion->subtotal_mano_obra)<tr><td>Mano de obra</td><td class="num">{{ $mx($cotizacion->subtotal_mano_obra) }}</td></tr>@endif
                @if($cotizacion->subtotal_refacciones)<tr><td>Refacciones</td><td class="num">{{ $mx($cotizacion->subtotal_refacciones) }}</td></tr>@endif
                @if($cotizacion->subtotal_otros)<tr><td>Otros</td><td class="num">{{ $mx($cotizacion->subtotal_otros) }}</td></tr>@endif
                @if($cotizacion->importe_cabina)<tr><td>Cabina</td><td class="num">{{ $mx($cotizacion->importe_cabina) }}</td></tr>@endif
                <tr><td>Subtotal</td><td class="num">{{ $mx($cotizacion->subtotal) }}</td></tr>
                <tr><td>IVA {{ $cotizacion->iva_tasa * 100 }}%</td><td class="num">{{ $mx($cotizacion->iva) }}</td></tr>
                <tr class="fuerte"><td>Total</td><td class="num">{{ $mx($cotizacion->total) }}</td></tr>
                @if($seguro)
                    <tr><td class="muted">Deducible</td><td class="num">{{ $mx($cotizacion->deducible) }}</td></tr>
                    @if($siniestro?->demerito)<tr><td class="muted">Demérito</td><td class="num">{{ $mx($siniestro->demerito) }}</td></tr>@endif
                    @if($siniestro?->salvamento)<tr><td class="muted">Salvamento</td><td class="num">−{{ $mx($siniestro->salvamento) }}</td></tr>@endif
                    <tr class="fuerte"><td>Paga la aseguradora</td><td class="num">{{ $mx($cotizacion->monto_aseguradora) }}</td></tr>
                    <tr class="fuerte"><td>Paga el cliente</td><td class="num">{{ $mx($cotizacion->monto_cliente) }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

@if($fotos->isNotEmpty())
<h2>Evidencia del diagnóstico</h2>
<div>@foreach($fotos as $uri)<div class="foto"><img src="{{ $uri }}" alt=""></div>@endforeach</div>
@endif

<p class="muted" style="margin-top:12px">Elaboró: {{ $cotizacion->autor?->name ?? $orden->asesor?->name ?? '—' }}. Ninguna reparación se realiza sin autorización. Precios sujetos a disponibilidad de refacciones.</p>
</body>
</html>
