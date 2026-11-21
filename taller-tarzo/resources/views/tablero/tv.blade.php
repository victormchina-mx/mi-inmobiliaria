@php($colores = ['ok' => '#2fbf71', 'cerca' => '#f5a524', 'excedido' => '#f04438'])
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="{{ $refresco }}">
    <meta name="robots" content="noindex">
    <title>Tablero · {{ config('tarzo.nombre_comercial') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #0f1416; color: #e8eef0; font: 16px/1.35 system-ui, -apple-system, "Segoe UI", sans-serif; }
        header { display: flex; justify-content: space-between; align-items: baseline; padding: 14px 20px; }
        header h1 { margin: 0; font-size: 26px; }
        header span { color: #93a3aa; font-size: 15px; }
        main { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(200px, 1fr); gap: 10px; padding: 0 12px 12px; height: calc(100vh - 64px); }
        section { background: #172024; border-radius: 10px; padding: 8px; display: flex; flex-direction: column; gap: 8px; overflow: hidden; }
        section h2 { margin: 0; font-size: 15px; text-transform: uppercase; letter-spacing: .04em; color: #93a3aa; display: flex; justify-content: space-between; }
        section h2 b { color: #e8eef0; }
        section h2 b.excedido { color: #f04438; }
        article { background: #1f2a2f; border-radius: 8px; padding: 8px 10px; border-left: 6px solid; }
        article .folio { font: 700 13px ui-monospace, monospace; color: #93a3aa; display: flex; justify-content: space-between; }
        article .auto { font-size: 18px; font-weight: 700; }
        article .info { font-size: 13px; color: #93a3aa; }
    </style>
</head>
<body>
<header>
    <h1>{{ config('tarzo.nombre_comercial') }} · Tablero del taller</h1>
    <span>{{ $columnas->sum(fn ($c) => $c['ordenes']->count()) }} autos · {{ now()->format('H:i') }}</span>
</header>
<main>
    @foreach ($columnas as $col)
        <section>
            <h2>{{ $col['etapa']->nombre }} <b class="{{ $col['excedido'] ? 'excedido' : '' }}">{{ $col['ordenes']->count() }}</b></h2>
            @foreach ($col['ordenes'] as $orden)
                @php($s = \App\Support\DatosTablero::semaforo($orden))
                @php($a = \App\Support\DatosTablero::avance($orden))
                <article style="border-color: {{ $colores[$s] }}">
                    <div class="folio"><span>{{ $orden->folio }}</span><span style="color: {{ $colores[$s] }}">{{ $orden->diasEnEtapa() }} d</span></div>
                    <div class="auto">{{ $orden->vehiculo->modelo }}@if ($orden->vehiculo->placas) · {{ $orden->vehiculo->placas }}@endif</div>
                    <div class="info">{{ \App\Support\DatosTablero::etiquetaTipo($orden) }}@if ($a['total']) · {{ $a['hechas'] }}/{{ $a['total'] }} @if ($a['tecnicos']) · {{ implode(' ', $a['tecnicos']) }}@endif @endif</div>
                </article>
            @endforeach
        </section>
    @endforeach
</main>
</body>
</html>
