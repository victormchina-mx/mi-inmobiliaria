<?php

namespace App\Mail;

use App\Models\Checkin;
use App\Support\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComprobanteIngreso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Checkin $checkin, public string $rutaPdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Inventario de ingreso · orden {$this->checkin->orden->folio}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.comprobante-ingreso');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(Media::nombreDisco(), $this->rutaPdf)
                ->as("ingreso-{$this->checkin->orden->folio}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
