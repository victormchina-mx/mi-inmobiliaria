@props(['alto' => 34, 'variante' => 'claro'])
{{-- Logotipo de TarsoHub (public/img/marca). "oscuro" = texto claro para fondos oscuros. --}}
<img src="{{ asset($variante === 'oscuro' ? 'img/marca/logo-oscuro.png' : 'img/marca/logo.png') }}"
     alt="{{ config('tarzo.nombre_comercial') }}" height="{{ $alto }}"
     {{ $attributes->merge(['style' => "height:{$alto}px;width:auto;display:block"]) }}>
