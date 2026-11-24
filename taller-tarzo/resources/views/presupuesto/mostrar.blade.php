@php
    use App\Enums\EstadoPartida;
    $mx = fn ($n) => '$'.number_format((float) $n, 2);
    $seguro = $cotizacion->esSeguro();
    $esperando = $cotizacion->estado === \App\Enums\EstadoCotizacion::Enviada;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $rol === 'ajustador' ? 'Valuación' : 'Presupuesto' }} {{ $cotizacion->folio }} · {{ config('tarzo.nombre_comercial') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/marca/icono-180.png') }}">
    @include('firma._estilos')
    <style>
        .partida { border-top: 1px solid var(--line); padding-top: 12px; display: grid; gap: 8px; }
        .partida:first-of-type { border-top: 0; padding-top: 0; }
        .fila { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; }
        .importe { font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .detalle { color: var(--muted); font-size: 14px; }
        .opciones { display: flex; gap: 8px; }
        .opciones label { flex: 1; display: flex; gap: 8px; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 10px; padding: 10px; font-weight: 600; cursor: pointer; }
        .opciones input { width: 20px; height: 20px; }
        .opciones label:has(input[value=aprobada]:checked) { border-color: var(--accent); background: #e2f1e9; color: var(--accent); }
        .opciones label:has(input[value=rechazada]:checked) { border-color: var(--danger); background: #fde8e6; color: var(--danger); }
        .estado { font-size: 13px; font-weight: 600; }
        .estado.aprobada { color: var(--accent); } .estado.rechazada { color: var(--danger); } .estado.pendiente { color: var(--muted); }
        .totales { display: grid; grid-template-columns: 1fr auto; gap: 4px 12px; font-variant-numeric: tabular-nums; }
        .totales .fuerte { font-weight: 700; font-size: 18px; }
        textarea { width: 100%; font: inherit; padding: 12px; border: 1px solid var(--line); border-radius: 8px; min-height: 80px; }
    </style>
</head>
<body>
<main>
    <header>
        <h1 class="logo" style="margin:0"><x-marca /></h1>
        <p>{{ $rol === 'ajustador' ? 'Valuación' : 'Presupuesto' }} <strong>{{ $cotizacion->folio }}</strong> · {{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }} {{ $orden->vehiculo->anio }}@if($orden->vehiculo->placas) · {{ $orden->vehiculo->placas }}@endif</p>
    </header>

    @if (session('respondido'))
        <div class="ok" role="status">Gracias, recibimos tu respuesta. El taller ya fue notificado.</div>
    @endif

    @if ($pendientes->isEmpty() && ! session('respondido'))
        <div class="ok">No tienes partidas pendientes de autorizar en este presupuesto.</div>
    @endif

    @if ($pendientes->isNotEmpty() && $esperando)
    <form class="card" method="POST" action="{{ $urlEnvio }}">
        @csrf
        <h2>Partidas por autorizar</h2>
        <p class="detalle">Todas vienen marcadas como autorizadas. Cambia a "No autorizo" las que no quieras que se hagan.</p>

        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        @foreach ($pendientes as $p)
            <div class="partida">
                <div class="fila">
                    <strong>{{ $p->descripcion }}</strong>
                    <span class="importe">{{ $mx($p->importe) }}</span>
                </div>
                <div class="detalle">
                    {{ $p->tipo->getLabel() }}
                    @if ($p->operacion_hyp) · {{ $p->operacion_hyp->getLabel() }}@endif
                    @if ($p->calidad) · {{ $p->calidad->getLabel() }}@endif
                    @if ($p->cantidad > 1) · {{ rtrim(rtrim(number_format($p->cantidad, 2), '0'), '.') }} pzas.@endif
                    @if ($p->diagnostico?->detalle)<br>{{ $p->diagnostico->detalle }}@endif
                </div>
                @php($imgs = $fotos($p))
                @if ($imgs->isNotEmpty())
                    <div class="fotos">@foreach ($imgs as $url)<img src="{{ $url }}" alt="Foto del hallazgo" loading="lazy">@endforeach</div>
                @endif
                <div class="opciones">
                    <label><input type="radio" name="decision[{{ $p->id }}]" value="aprobada" @checked(old("decision.{$p->id}", 'aprobada') === 'aprobada')> Autorizo</label>
                    <label><input type="radio" name="decision[{{ $p->id }}]" value="rechazada" @checked(old("decision.{$p->id}") === 'rechazada')> No autorizo</label>
                </div>
            </div>
        @endforeach

        <label for="comentario"><strong>Comentario (opcional)</strong></label>
        <textarea id="comentario" name="comentario" maxlength="1000" placeholder="Ej.: autorizo solo con refacción original">{{ old('comentario') }}</textarea>

        <label for="nombre"><strong>Nombre de quien autoriza</strong></label>
        <input type="text" id="nombre" name="nombre" required minlength="3" maxlength="160"
            value="{{ old('nombre', $rol === 'ajustador' ? ($orden->siniestro?->ajustador_nombre ?? '') : $orden->cliente->nombre_completo) }}">

        <label class="check"><input type="checkbox" name="acepta" value="1" required> Confirmo mi respuesta. Entiendo que solo se realizarán los trabajos autorizados.</label>

        <button type="submit" class="primario">Enviar respuesta</button>
    </form>
    @endif

    <section class="card">
        <h2>Detalle del presupuesto</h2>
        @foreach ($mias->merge($otras) as $p)
            @continue($p->estado === EstadoPartida::Pendiente && $mias->contains($p) && $esperando)
            <div class="fila">
                <span>{{ $p->descripcion }}@if ($seguro)<span class="detalle"> · paga {{ strtolower($p->paga->getLabel()) }}</span>@endif<br><span class="estado {{ $p->estado->value }}">{{ $p->estado->getLabel() }}</span></span>
                <span class="importe">{{ $mx($p->importe) }}</span>
            </div>
        @endforeach
        <div class="totales">
            @if ($cotizacion->importe_cabina)
                <span>Cabina de pintura ({{ $cotizacion->ciclos_cabina }} {{ $cotizacion->ciclos_cabina === 1 ? 'ciclo' : 'ciclos' }} de horneado)</span><span>{{ $mx($cotizacion->importe_cabina) }}</span>
            @endif
            <span>Subtotal</span><span>{{ $mx($cotizacion->subtotal) }}</span>
            <span>IVA</span><span>{{ $mx($cotizacion->iva) }}</span>
            <span class="fuerte">Total</span><span class="fuerte">{{ $mx($cotizacion->total) }}</span>
            @if ($seguro)
                <span>Deducible</span><span>{{ $mx($cotizacion->deducible) }}</span>
                <span>Paga la aseguradora</span><span>{{ $mx($cotizacion->monto_aseguradora) }}</span>
                <span><strong>Paga el cliente</strong></span><span><strong>{{ $mx($cotizacion->monto_cliente) }}</strong></span>
            @endif
        </div>
        <p class="detalle">Importes con IVA incluido en los totales. Las partidas no autorizadas no se cobran. Vigencia: {{ $cotizacion->vigencia_dias }} días.</p>
    </section>
</main>
</body>
</html>
