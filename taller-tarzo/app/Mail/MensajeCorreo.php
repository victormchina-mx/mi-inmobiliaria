<?php

namespace App\Mail;

use App\Models\Mensaje;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Correo genérico a partir de un Mensaje ya armado con su plantilla.
 */
class MensajeCorreo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Mensaje $mensaje) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mensaje->asunto ?: config('tarzo.nombre_comercial'));
    }

    public function content(): Content
    {
        $baja = $this->mensaje->evento?->esAdicional() && $this->mensaje->cliente
            ? url(URL::signedRoute('baja.recordatorios', ['cliente' => $this->mensaje->cliente_id], absolute: false))
            : null;

        return new Content(markdown: 'mail.mensaje', with: ['cuerpo' => $this->mensaje->cuerpo, 'baja' => $baja]);
    }
}
