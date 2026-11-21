<?php

namespace App\Mail;

use App\Models\Etapa;
use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoEtapa extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public OrdenServicio $orden, public Etapa $etapa) {}

    public function envelope(): Envelope
    {
        $v = $this->orden->vehiculo;

        return new Envelope(subject: $this->etapa->clave === Etapa::LISTO_ENTREGA
            ? "Tu {$v->marca} {$v->modelo} está listo"
            : "Tu {$v->marca} {$v->modelo} pasó a {$this->etapa->nombre}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.aviso-etapa');
    }
}
