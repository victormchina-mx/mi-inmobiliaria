<?php

namespace App\Support;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * PDF del presupuesto (para el cliente) o de la valuación (para la aseguradora).
 */
final class PdfCotizacion
{
    public const FORMATOS = ['presupuesto', 'valuacion'];

    public static function generar(Cotizacion $cotizacion, string $formato = 'presupuesto'): DomPdf
    {
        $cotizacion->loadMissing(['partidas.pieza', 'partidas.area', 'partidas.diagnostico', 'orden.cliente', 'orden.vehiculo', 'orden.siniestro.aseguradora', 'orden.asesor', 'aseguradora']);

        return Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'orden' => $cotizacion->orden,
            'formato' => in_array($formato, self::FORMATOS, true) ? $formato : 'presupuesto',
        ])->setPaper('letter')->setOption('isRemoteEnabled', false);
    }
}
