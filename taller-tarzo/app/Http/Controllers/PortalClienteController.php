<?php

namespace App\Http\Controllers;

use App\Actions\Cotizacion\EnlaceAprobacion;
use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal de seguimiento para el cliente (enlace firmado, sin contraseña).
 */
class PortalClienteController extends Controller
{
    public function orden(OrdenServicio $orden): View
    {
        $orden->load(['vehiculo', 'cliente', 'etapa', 'cotizacionActual', 'garantias']);
        $historial = $orden->historialEtapas()->get()->reverse()->values();

        $cotizacion = $orden->cotizacionActual;
        $enlaceCotizacion = $cotizacion && $cotizacion->estado === EstadoCotizacion::Enviada
            ? EnlaceAprobacion::generar($cotizacion, 'cliente') : null;

        return view('portal.orden', [
            'orden' => $orden,
            'historial' => $historial,
            'etapas' => Etapa::ordenadas()->get(),
            'fotos' => $orden->evidencias()->where('visible_cliente', true)->latest()->limit(24)->get()
                ->map(fn ($e) => ['url' => Media::urlTemporal($e->archivo, 120), 'texto' => $e->momento->getLabel().($e->descripcion ? ' · '.$e->descripcion : '')])
                ->filter(fn ($f) => $f['url']),
            'cotizacion' => $cotizacion,
            'enlaceCotizacion' => $enlaceCotizacion,
        ]);
    }

    public function baja(Request $request, Cliente $cliente): View
    {
        $cliente->update(['acepta_recordatorios' => false, 'acepta_promociones' => false]);

        return view('portal.baja', ['cliente' => $cliente]);
    }
}
