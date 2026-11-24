<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Inventario de ingreso {{ $orden->folio }} · {{ config('tarzo.nombre_comercial') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/marca/icono-180.png') }}">
    @include('firma._estilos')
</head>
<body>
<main>
    <header>
        <h1 class="logo" style="margin:0"><x-marca /></h1>
        <p>Inventario de ingreso · orden <strong>{{ $orden->folio }}</strong></p>
    </header>

    @if ($checkin->estaFirmado())
        <div class="ok">Este inventario ya fue firmado por {{ $checkin->firmado_por }} el {{ $checkin->firmado_at->translatedFormat('j \d\e F \a \l\a\s H:i') }}. Te enviamos una copia en PDF.</div>
    @endif

    <section class="card">
        <h2>Vehículo</h2>
        <dl>
            <dt>Cliente</dt><dd>{{ $orden->cliente->nombre_completo }}</dd>
            <dt>Vehículo</dt><dd>{{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }} {{ $orden->vehiculo->anio }}</dd>
            @if ($orden->vehiculo->placas)<dt>Placas</dt><dd>{{ $orden->vehiculo->placas }}</dd>@endif
            <dt>Kilometraje</dt><dd>{{ $checkin->kilometraje ? number_format($checkin->kilometraje).' km' : 'No registrado' }}</dd>
            <dt>Gasolina</dt><dd>{{ $checkin->nivel_combustible !== null ? $checkin->nivel_combustible.'/8' : 'No registrado' }}</dd>
            <dt>Recibido</dt><dd>{{ $orden->fecha_ingreso->translatedFormat('j M Y, H:i') }}</dd>
        </dl>
    </section>

    <section class="card">
        <h2>Daños previos</h2>
        @if ($checkin->danos->isEmpty())
            <p>Sin daños previos registrados.</p>
        @else
            <div class="danos">
                <div>{!! \App\Support\DiagramaDanos::svg($checkin->danos->pluck('zona'), 120) !!}</div>
                <ul>
                    @foreach ($checkin->danos as $dano)
                        <li><strong>{{ $dano->zona->getLabel() }}:</strong> {{ $dano->tipo->getLabel() }}@if($dano->descripcion) · {{ $dano->descripcion }}@endif</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($fotos->isNotEmpty())
            <div class="fotos">
                @foreach ($fotos as $url)
                    <img src="{{ $url }}" alt="Foto del vehículo al ingresar" loading="lazy">
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <h2>Tablero e inventario</h2>
        <div><strong>Testigos encendidos:</strong>
            <div class="chips">
                @forelse ($checkin->testigosLegibles() as $t)<span class="chip falta">{{ $t }}</span>@empty<span class="chip">Ninguno</span>@endforelse
            </div>
        </div>
        <div><strong>Se recibe con:</strong>
            <div class="chips">@forelse ($checkin->inventarioLegible() as $i)<span class="chip">{{ $i }}</span>@empty<span class="chip">Nada registrado</span>@endforelse</div>
        </div>
        @if ($checkin->pertenencias)<div><strong>Pertenencias declaradas:</strong> {{ $checkin->pertenencias }}</div>@endif
        @if ($checkin->observaciones)<div><strong>Observaciones:</strong> {{ $checkin->observaciones }}</div>@endif
    </section>

    @unless ($checkin->estaFirmado())
    <form class="card" method="POST" action="{{ $urlEnvio }}" id="form-firma">
        @csrf
        <h2>Condiciones del servicio</h2>
        <div class="legal">@include('legal.condiciones')</div>
        <h2>Aviso de privacidad</h2>
        <div class="legal">@include('legal.aviso-privacidad')</div>

        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        <label class="check"><input type="checkbox" name="acepta_condiciones" value="1" @checked(old('acepta_condiciones')) required> Acepto las condiciones del servicio y que el inventario refleja el estado de mi vehículo.</label>
        <label class="check"><input type="checkbox" name="acepta_aviso_privacidad" value="1" @checked(old('acepta_aviso_privacidad')) required> He leído el aviso de privacidad.</label>

        <label for="nombre"><strong>Nombre de quien firma</strong></label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $orden->cliente->nombre_completo) }}" autocomplete="name" required minlength="3" maxlength="160">

        <strong>Firma</strong>
        <div class="lienzo"><canvas id="lienzo" aria-label="Recuadro para firmar con el dedo"></canvas><span id="pista">Firma aquí con tu dedo</span></div>
        <input type="hidden" name="firma" id="firma">

        <div class="error" id="aviso-firma" role="alert" hidden>Firma dentro del recuadro antes de continuar.</div>
        <div class="acciones">
            <button type="button" class="secundario" id="borrar">Borrar firma</button>
            <button type="submit" class="primario">Firmar inventario</button>
        </div>
    </form>
    @endunless
</main>

@unless ($checkin->estaFirmado())
<script>
(() => {
    const canvas = document.getElementById('lienzo');
    const ctx = canvas.getContext('2d');
    const pista = document.getElementById('pista');
    let dibujando = false, trazos = 0, ultimo = null;

    function ajustar() {
        const r = canvas.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
        canvas.width = r.width * dpr; canvas.height = r.height * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111';
        trazos = 0; pista.hidden = false;
    }
    function punto(e) { const r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }

    canvas.addEventListener('pointerdown', e => { dibujando = true; ultimo = punto(e); canvas.setPointerCapture(e.pointerId); pista.hidden = true; });
    canvas.addEventListener('pointermove', e => {
        if (!dibujando) return;
        const p = punto(e);
        ctx.beginPath(); ctx.moveTo(ultimo.x, ultimo.y); ctx.lineTo(p.x, p.y); ctx.stroke();
        ultimo = p; trazos++;
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(ev => canvas.addEventListener(ev, () => { dibujando = false; }));
    document.getElementById('borrar').addEventListener('click', ajustar);
    document.getElementById('form-firma').addEventListener('submit', e => {
        const aviso = document.getElementById('aviso-firma');
        if (trazos < 15) { e.preventDefault(); aviso.hidden = false; canvas.scrollIntoView({ block: 'center' }); return; }
        aviso.hidden = true;
        document.getElementById('firma').value = canvas.toDataURL('image/png');
    });
    window.addEventListener('resize', ajustar);
    ajustar();
})();
</script>
@endunless
</body>
</html>
