<?php

namespace App\Http\Controllers;

use App\Jobs\GenerarPdfIngreso;
use App\Models\OrdenServicio;
use App\Support\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga del PDF de ingreso desde el panel (requiere sesión y permiso).
 */
class DocumentoIngresoController extends Controller
{
    public function __invoke(OrdenServicio $orden): StreamedResponse
    {
        abort_unless(auth()->user()?->can('recepcion.ver'), 403);

        $checkin = $orden->checkin;
        abort_if($checkin === null, 404, 'La orden todavía no tiene check-in.');

        $ruta = $checkin->pdf && Media::disco()->exists($checkin->pdf)
            ? $checkin->pdf
            : GenerarPdfIngreso::generar($checkin->load(['orden.cliente', 'orden.vehiculo', 'orden.asesor', 'danos']));

        return Media::disco()->download($ruta, "ingreso-{$orden->folio}.pdf", ['Content-Type' => 'application/pdf']);
    }
}
