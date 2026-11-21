<?php

namespace App\Mail;

use App\Actions\Cotizacion\EnlaceAprobacion;
use App\Models\Cotizacion;
use App\Support\PdfCotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PresupuestoParaAutorizar extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Cotizacion $cotizacion, public string $rol = 'cliente', public bool $esRecordatorio = false) {}

    public function envelope(): Envelope
    {
        $prefijo = $this->esRecordatorio ? 'Recordatorio: ' : '';
        $asunto = $this->rol === 'ajustador'
            ? "Valuación siniestro {$this->cotizacion->orden->siniestro?->numero} · {$this->cotizacion->folio}"
            : "Presupuesto de tu vehículo · {$this->cotizacion->folio}";

        return new Envelope(subject: $prefijo.$asunto);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.presupuesto', with: [
            'enlace' => EnlaceAprobacion::generar($this->cotizacion, $this->rol),
            'horas' => EnlaceAprobacion::HORAS_VIGENCIA,
        ]);
    }

    public function attachments(): array
    {
        $formato = $this->rol === 'ajustador' ? 'valuacion' : 'presupuesto';

        return [
            Attachment::fromData(fn () => PdfCotizacion::generar($this->cotizacion, $formato)->output(), "{$this->cotizacion->folio}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
