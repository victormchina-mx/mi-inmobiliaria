<x-mail::message>
@if ($rol === 'ajustador')
# Valuación para autorizar

Le compartimos la valuación de la orden **{{ $cotizacion->orden->folio }}**
@if ($cotizacion->orden->siniestro?->numero) (siniestro {{ $cotizacion->orden->siniestro->numero }})@endif
del vehículo **{{ $cotizacion->orden->vehiculo->descripcion }}**.
@else
# Tu presupuesto está listo

Hola {{ $cotizacion->orden->cliente->nombre }}: ya tenemos el presupuesto de tu **{{ $cotizacion->orden->vehiculo->marca }} {{ $cotizacion->orden->vehiculo->modelo }}** (orden {{ $cotizacion->orden->folio }}).
@endif

Puedes revisar cada concepto con sus fotos y autorizar o rechazar partida por partida desde tu celular, sin crear cuenta.

<x-mail::button :url="$enlace">
Revisar y autorizar
</x-mail::button>

El enlace vence en {{ $horas }} horas. También adjuntamos el PDF.

Gracias,<br>
{{ config('tarzo.taller.razon_social') }}
@if (config('tarzo.taller.telefono'))
<br>Tel. {{ config('tarzo.taller.telefono') }}
@endif
</x-mail::message>
