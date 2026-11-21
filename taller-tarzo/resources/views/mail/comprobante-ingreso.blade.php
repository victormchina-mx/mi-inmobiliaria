<x-mail::message>
# Recibimos tu vehículo

Hola {{ $checkin->orden->cliente->nombre }}:

Tu **{{ $checkin->orden->vehiculo->marca }} {{ $checkin->orden->vehiculo->modelo }}** quedó registrado con la orden **{{ $checkin->orden->folio }}**.

Adjuntamos el inventario de ingreso firmado, con las fotografías y el estado del vehículo al recibirlo. Te avisaremos cuando tengamos el diagnóstico y el presupuesto.

Gracias por tu confianza,<br>
{{ config('tarzo.taller.razon_social') }}
@if (config('tarzo.taller.telefono'))
<br>Tel. {{ config('tarzo.taller.telefono') }}
@endif
</x-mail::message>
