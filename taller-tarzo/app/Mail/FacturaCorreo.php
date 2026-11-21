<?php

namespace App\Mail;

use App\Models\Factura;
use App\Support\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envía el XML y el PDF de un CFDI al receptor.
 */
class FacturaCorreo extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Factura $factura) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->factura->etiquetaTipo()} {$this->factura->folio_completo} · ".config('tarzo.cfdi.emisor.nombre'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.factura', with: ['f' => $this->factura]);
    }

    public function attachments(): array
    {
        $nombre = "{$this->factura->receptor_rfc}-{$this->factura->folio_completo}";

        return collect(['xml' => $this->factura->xml_path, 'pdf' => $this->factura->pdf_path])->filter()
            ->map(fn ($ruta, $ext) => Attachment::fromStorageDisk(Media::nombreDisco(), $ruta)->as("{$nombre}.{$ext}"))
            ->values()->all();
    }
}
