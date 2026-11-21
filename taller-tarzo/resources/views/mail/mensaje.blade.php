<x-mail::message>
{!! nl2br(preg_replace('~(https?://[^\s<]+)~', '<a href="$1">$1</a>', e($cuerpo))) !!}

{{ config('tarzo.taller.razon_social') ?: config('tarzo.nombre_comercial') }}
@if (config('tarzo.taller.telefono'))
<br>Tel. {{ config('tarzo.taller.telefono') }}
@endif

@if ($baja)
<x-slot:subcopy>
¿No quieres recibir recordatorios ni encuestas? [Darme de baja]({{ $baja }}). Los avisos de tus órdenes seguirán llegando.
</x-slot:subcopy>
@endif
</x-mail::message>
