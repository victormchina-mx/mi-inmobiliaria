<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Pago;
use App\Support\Media;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * XML y PDF de facturas y recibos de caja (solo usuarios con permiso de facturación).
 */
class FacturacionArchivosController extends Controller
{
    public function factura(Factura $factura, string $formato): Response
    {
        abort_unless(auth()->user()?->can('facturacion.ver'), 403);
        $ruta = $formato === 'xml' ? $factura->xml_path : $factura->pdf_path;
        abort_unless($ruta && Media::disco()->exists($ruta), 404);

        $nombre = "{$factura->receptor_rfc}-{$factura->folio_completo}.{$formato}";

        return Media::disco()->download($ruta, $nombre, ['Content-Type' => $formato === 'xml' ? 'application/xml' : 'application/pdf']);
    }

    public function recibo(Pago $pago): Response
    {
        abort_unless(auth()->user()?->can('facturacion.ver'), 403);
        $pago->load(['aplicaciones.factura', 'cliente', 'aseguradora', 'orden.vehiculo', 'cajero']);

        return Pdf::loadView('pdf.recibo', ['pago' => $pago])->setPaper([0, 0, 396, 612], 'landscape')->stream("recibo-{$pago->folio}.pdf");
    }
}
