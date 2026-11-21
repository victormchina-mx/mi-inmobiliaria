<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Support\PdfCotizacion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CotizacionPdfController extends Controller
{
    public function __invoke(Request $request, Cotizacion $cotizacion): Response
    {
        abort_unless($request->user()?->can('cotizacion.ver'), 403);

        $formato = $request->query('formato', 'presupuesto');

        return PdfCotizacion::generar($cotizacion, $formato)
            ->stream("{$cotizacion->folio}".($formato === 'valuacion' ? '-valuacion' : '').'.pdf');
    }
}
