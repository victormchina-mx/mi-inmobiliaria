<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>¿Cómo te atendimos? · {{ config('tarzo.nombre_comercial') }}</title>
    @include('firma._estilos')
    <style>
        .escala { display: grid; grid-template-columns: repeat(11, 1fr); gap: 4px; }
        .escala label { display: grid; place-items: center; border: 1px solid var(--line); border-radius: 8px; height: 44px; font-weight: 700; cursor: pointer; }
        .escala input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .escala label:has(input:checked) { background: var(--accent); color: #fff; border-color: var(--accent); }
        .escala label:has(input:focus-visible) { outline: 3px solid var(--accent); outline-offset: 2px; }
        .extremos { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); }
        textarea { width: 100%; font: inherit; padding: 12px; border: 1px solid var(--line); border-radius: 8px; min-height: 90px; }
        a.boton { display: block; text-align: center; background: var(--accent); color: #fff; text-decoration: none; font-weight: 600; padding: 14px; border-radius: 10px; }
    </style>
</head>
<body>
<main>
    <header>
        <h1>{{ config('tarzo.nombre_comercial') }}</h1>
        <p>{{ $encuesta->orden->vehiculo->marca }} {{ $encuesta->orden->vehiculo->modelo }} · orden {{ $encuesta->orden->folio }}</p>
    </header>

    @if ($encuesta->respondida_at)
        <section class="card">
            <h2>¡Gracias, {{ $encuesta->cliente->nombre }}!</h2>
            @if ($encuesta->calificacion >= 9)
                <p>Nos da mucho gusto que te haya gustado el servicio.</p>
                @if ($urlResena)
                    <p>¿Nos ayudas contándolo en Google? Le sirve mucho a otros clientes.</p>
                    <a class="boton" href="{{ $urlResena }}" target="_blank" rel="noopener">Escribir una reseña</a>
                @endif
            @elseif ($encuesta->calificacion <= 6)
                <p>Lamentamos que no fuera la experiencia que esperabas. Alguien del taller se pondrá en contacto contigo para resolverlo.</p>
            @else
                <p>Tomamos en cuenta tu opinión para seguir mejorando.</p>
            @endif
        </section>
    @else
        <form class="card" method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <h2>¿Qué tan probable es que nos recomiendes con un familiar o amigo?</h2>
            @if ($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
            <div class="escala" role="radiogroup" aria-label="Calificación de 0 a 10">
                @for ($i = 0; $i <= 10; $i++)
                    <label><input type="radio" name="calificacion" value="{{ $i }}" required @checked(old('calificacion') === (string) $i)>{{ $i }}</label>
                @endfor
            </div>
            <div class="extremos"><span>Nada probable</span><span>Muy probable</span></div>
            <label for="comentario"><strong>¿Qué podríamos mejorar? (opcional)</strong></label>
            <textarea id="comentario" name="comentario" maxlength="2000">{{ old('comentario') }}</textarea>
            <button type="submit" class="primario">Enviar</button>
        </form>
    @endif
</main>
</body>
</html>
