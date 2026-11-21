<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Firma recibida · {{ config('tarzo.nombre_comercial') }}</title>
    @include('firma._estilos')
</head>
<body>
<main>
    <header><h1>{{ config('tarzo.nombre_comercial') }}</h1></header>
    <div class="ok">Gracias. Recibimos tu firma del inventario de ingreso{{ $folio ? ' de la orden '.$folio : '' }}. Te enviaremos una copia en PDF y te avisaremos del avance de tu vehículo.</div>
</main>
</body>
</html>
