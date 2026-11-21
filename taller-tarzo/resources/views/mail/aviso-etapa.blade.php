<x-mail::message>
@php($v = $orden->vehiculo)
@if ($etapa->clave === \App\Models\Etapa::LISTO_ENTREGA)
# Tu vehículo está listo

Hola {{ $orden->cliente->nombre }}: tu **{{ $v->marca }} {{ $v->modelo }}** (orden {{ $orden->folio }}) ya pasó el control de calidad y está listo para entrega.
@elseif ($etapa->clave === \App\Models\Etapa::ENTREGADO)
# Gracias por tu visita

Hola {{ $orden->cliente->nombre }}: registramos la entrega de tu **{{ $v->marca }} {{ $v->modelo }}** (orden {{ $orden->folio }}). Cualquier duda sobre el trabajo o la garantía, estamos para ayudarte.
@else
# Avance de tu vehículo

Hola {{ $orden->cliente->nombre }}: tu **{{ $v->marca }} {{ $v->modelo }}** (orden {{ $orden->folio }}) pasó a la etapa **{{ $etapa->nombre }}**.
@endif

@if ($orden->fecha_promesa && $etapa->clave !== \App\Models\Etapa::ENTREGADO)
Fecha estimada de entrega: {{ $orden->fecha_promesa->translatedFormat('l j \d\e F, H:i') }}.
@endif

{{ config('tarzo.taller.razon_social') }}
@if (config('tarzo.taller.telefono'))
<br>Tel. {{ config('tarzo.taller.telefono') }}
@endif
</x-mail::message>
