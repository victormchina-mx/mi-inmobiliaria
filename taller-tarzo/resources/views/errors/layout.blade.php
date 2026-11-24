<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('titulo') · {{ config('tarzo.nombre_comercial') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/marca/icono-180.png') }}">
    @include('firma._estilos')
</head>
<body>
<main>
    <header><h1 class="logo" style="margin:0"><x-marca /></h1></header>
    <section class="card">
        <h2>@yield('titulo')</h2>
        <p>@yield('mensaje')</p>
        @if (config('tarzo.taller.telefono'))
            <p>Si necesitas ayuda, llámanos al {{ config('tarzo.taller.telefono') }}.</p>
        @endif
    </section>
</main>
</body>
</html>
