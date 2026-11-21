@php($t = config('tarzo.taller'))
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Evidencias {{ $orden->folio }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1a2124; }
    h1 { font-size: 15px; margin: 0; }
    h2 { font-size: 10.5px; margin: 12px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #0d7a4a; color: #0d7a4a; text-transform: uppercase; }
    .foto { width: 48%; display: inline-block; margin: 0 1% 10px 0; vertical-align: top; page-break-inside: avoid; }
    .foto img { width: 100%; height: 200px; }
    .muted { color: #5a666c; }
</style>
</head>
<body>
<h1>{{ $t['razon_social'] }} · Evidencia fotográfica</h1>
<p>Orden <strong>{{ $orden->folio }}</strong> · {{ $orden->vehiculo->descripcion }} · {{ $orden->cliente->nombre_completo }}
@if ($orden->numero_siniestro) · Siniestro {{ $orden->numero_siniestro }}@endif</p>
@forelse ($evidencias->groupBy(fn ($e) => $e->etapa?->nombre ?? 'Sin etapa') as $etapa => $grupo)
    <h2>{{ $etapa }}</h2>
    @foreach ($grupo as $e)
        @if ($uri = \App\Support\Media::dataUri($e->archivo))
            <div class="foto"><img src="{{ $uri }}" alt=""><div class="muted">{{ $e->momento->getLabel() }}@if ($e->descripcion) · {{ $e->descripcion }}@endif · {{ $e->created_at->format('d/m/Y H:i') }}</div></div>
        @endif
    @endforeach
@empty
    <p>No hay evidencias {{ $soloCliente ? 'marcadas como visibles para el cliente' : 'registradas' }}.</p>
@endforelse
</body>
</html>
