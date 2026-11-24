<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aviso de privacidad · {{ config('tarzo.nombre_comercial') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/marca/icono-180.png') }}">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 0 auto; padding: 24px 16px 48px; color: #1a2124; line-height: 1.6; background: #fff; }
        h1 { font-size: 24px; }
    </style>
</head>
<body>
    <h1>Aviso de privacidad</h1>
    @include('legal.aviso-privacidad')
</body>
</html>
