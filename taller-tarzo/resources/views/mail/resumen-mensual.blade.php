<x-mail::message>
# Indicadores de {{ $mes }}

<x-mail::table>
| Indicador | Valor |
|:--|--:|
@foreach ($datos['kpis'] as $k)
| {{ $k['nombre'] }} | {{ $k['valor'] === null ? '—' : match ($k['formato']) { 'dinero' => '$'.number_format($k['valor'], 2), 'porcentaje' => number_format($k['valor'], 1).' %', 'dias' => number_format($k['valor'], 1).' días', 'veces' => number_format($k['valor'], 2).'×', default => number_format($k['valor']) } }} |
@endforeach
</x-mail::table>

@if ($datos['cuello'])
**Cuello de botella:** {{ $datos['cuello']['etapa'] }} ({{ number_format($datos['cuello']['dias'], 1) }} días promedio).
@endif

El detalle (días por etapa, técnicos y utilidad por orden) va en el Excel adjunto.
</x-mail::message>
