<?php

namespace App\Mail;

use App\Services\Finanzas\Indicadores;
use App\Support\ExportarIndicadores;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ResumenMensual extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $mes) {}

    private function periodo(): array
    {
        $inicio = Carbon::parse($this->mes)->startOfMonth();

        return [$inicio, $inicio->copy()->endOfMonth()];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Indicadores de '.Carbon::parse($this->mes)->translatedFormat('F Y').' · '.config('tarzo.nombre_comercial'));
    }

    public function content(): Content
    {
        [$desde, $hasta] = $this->periodo();

        return new Content(markdown: 'mail.resumen-mensual', with: [
            'datos' => app(Indicadores::class)->calcular($desde, $hasta),
            'mes' => $desde->translatedFormat('F Y'),
        ]);
    }

    public function attachments(): array
    {
        [$desde, $hasta] = $this->periodo();

        return [Attachment::fromData(fn () => ExportarIndicadores::xlsx($desde, $hasta), "indicadores-{$desde->format('Y-m')}.xlsx")
            ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')];
    }
}
