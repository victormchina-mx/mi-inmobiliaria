<?php

namespace App\Jobs;

use App\Mail\ComprobanteIngreso;
use App\Models\Checkin;
use App\Support\Media;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Genera el PDF del inventario de ingreso y, si se pide, lo envía al cliente por correo.
 */
class GenerarPdfIngreso implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 45; // debe caber en la ventana del cron del hosting compartido

    public function __construct(public int $checkinId, public bool $enviarAlCliente = false)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $checkin = Checkin::with(['orden.cliente', 'orden.vehiculo', 'orden.asesor', 'danos'])->findOrFail($this->checkinId);

        $ruta = self::generar($checkin);

        if ($this->enviarAlCliente && filled($checkin->orden->cliente->email)) {
            Mail::to($checkin->orden->cliente->email)->send(new ComprobanteIngreso($checkin->fresh(), $ruta));
        }
    }

    public static function generar(Checkin $checkin): string
    {
        $pdf = Pdf::loadView('pdf.ingreso', ['checkin' => $checkin, 'orden' => $checkin->orden])
            ->setPaper('letter')
            ->setOption('isRemoteEnabled', false);

        $ruta = "ingresos/{$checkin->orden->folio}.pdf";
        Media::disco()->put($ruta, $pdf->output());

        $checkin->forceFill(['pdf' => $ruta, 'pdf_generado_at' => now()])->saveQuietly();

        return $ruta;
    }
}
