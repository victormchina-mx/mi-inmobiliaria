<x-mail::message>
# {{ $f->etiquetaTipo() }} {{ $f->folio_completo }}

Hola, {{ $f->receptor_nombre }}:

Te enviamos el comprobante fiscal (XML y PDF) por **${{ number_format($f->total, 2) }} MXN**@if ($f->orden) de la orden {{ $f->orden->folio }}@endif.

Folio fiscal: {{ $f->uuid }}

@if ($f->tipo === 'I' && $f->metodo_pago === 'PPD' && $f->vence)
Fecha de pago: {{ $f->vence->format('d/m/Y') }}.
@endif

Gracias,<br>
{{ config('tarzo.cfdi.emisor.nombre') }}
</x-mail::message>
