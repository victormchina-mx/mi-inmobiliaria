@php
    $actual = $orden->etapa;
    $visibles = $etapas->where('es_final', false)->values();
    $indice = $visibles->search(fn ($e) => $e->id === $actual->id);
    $entregado = $actual->es_final;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }} · {{ config('tarzo.nombre_comercial') }}</title>
    @include('firma._estilos')
    <style>
        .pasos { list-style: none; margin: 0; padding: 0; display: grid; gap: 0; }
        .pasos li { display: grid; grid-template-columns: 22px 1fr; gap: 10px; padding: 6px 0; color: var(--muted); }
        .pasos li::before { content: ""; width: 14px; height: 14px; border-radius: 50%; margin-top: 4px; background: var(--line); justify-self: center; }
        .pasos li.hecho { color: var(--fg); } .pasos li.hecho::before { background: var(--accent); }
        .pasos li.actual { color: var(--fg); font-weight: 700; } .pasos li.actual::before { background: #fff; border: 4px solid var(--accent); width: 10px; height: 10px; }
        .grande { font-size: 20px; font-weight: 700; }
        .fotos figure { margin: 0; } .fotos figcaption { font-size: 12px; color: var(--muted); }
        a.boton { display: block; text-align: center; background: var(--accent); color: #fff; text-decoration: none; font-weight: 600; padding: 14px; border-radius: 10px; }
    </style>
</head>
<body>
<main>
    <header>
        <h1>{{ config('tarzo.nombre_comercial') }}</h1>
        <p>{{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }} {{ $orden->vehiculo->anio }}@if ($orden->vehiculo->placas) · {{ $orden->vehiculo->placas }}@endif · orden {{ $orden->folio }}</p>
    </header>

    <section class="card">
        <span class="detalle" style="color:var(--muted)">Estado actual</span>
        <div class="grande">{{ $entregado ? 'Entregado' : $actual->nombre }}</div>
        @if (! $entregado && $orden->fecha_promesa)
            <div>Entrega estimada: <strong>{{ $orden->fecha_promesa->translatedFormat('l j \d\e F, H:i') }}</strong></div>
        @endif
        @if ($entregado && $orden->fecha_entrega)
            <div>Entregado el {{ $orden->fecha_entrega->translatedFormat('j \d\e F Y') }}</div>
        @endif
    </section>

    @if ($enlaceCotizacion)
        <section class="card">
            <h2>Tu presupuesto está listo</h2>
            <p>Revisa cada concepto y autoriza lo que quieras que hagamos.</p>
            <a class="boton" href="{{ $enlaceCotizacion }}">Revisar y autorizar presupuesto</a>
        </section>
    @endif

    @unless ($entregado)
    <section class="card">
        <h2>Avance</h2>
        <ol class="pasos">
            @foreach ($visibles as $i => $e)
                <li class="{{ $indice !== false && $i < $indice ? 'hecho' : ($i === $indice ? 'actual' : '') }}">{{ $e->nombre }}</li>
            @endforeach
        </ol>
    </section>
    @endunless

    @if ($fotos->isNotEmpty())
        <section class="card">
            <h2>Fotos del trabajo</h2>
            <div class="fotos">
                @foreach ($fotos as $f)
                    <figure><img src="{{ $f['url'] }}" alt="{{ $f['texto'] }}" loading="lazy"><figcaption>{{ $f['texto'] }}</figcaption></figure>
                @endforeach
            </div>
        </section>
    @endif

    <section class="card">
        <h2>Historial</h2>
        <dl>
            @foreach ($historial as $h)
                <dt>{{ $h->created_at->translatedFormat('j M, H:i') }}</dt><dd>{{ $h->etapa->nombre }}</dd>
            @endforeach
        </dl>
    </section>

    @if ($orden->garantias->isNotEmpty())
        <section class="card">
            <h2>Garantías</h2>
            @foreach ($orden->garantias as $g)
                <div>{{ $g->tipo->getLabel() }}: hasta el {{ $g->vence->translatedFormat('j \d\e F Y') }}@if ($g->km_limite) o {{ number_format($g->km_limite) . " km" }}@endif</div>
            @endforeach
        </section>
    @endif

    <section class="card">
        <h2>¿Dudas?</h2>
        <p>{{ config('tarzo.taller.razon_social') ?: config('tarzo.nombre_comercial') }}@if (config('tarzo.taller.telefono')) · Tel. <strong>{{ config('tarzo.taller.telefono') }}</strong>@endif</p>
        @if (config('tarzo.taller.domicilio'))<p class="detalle" style="color:var(--muted)">{{ config('tarzo.taller.domicilio') }}</p>@endif
    </section>
</main>
</body>
</html>
