<?php

namespace App\Http\Controllers;

use App\Actions\Recepcion\FirmarCheckin;
use App\Models\Checkin;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Página pública (enlace firmado, sin cuenta) donde el cliente revisa y firma el inventario.
 */
class FirmaClienteController extends Controller
{
    public function mostrar(Checkin $checkin): View
    {
        $checkin->load(['orden.cliente', 'orden.vehiculo', 'danos']);

        return view('firma.mostrar', [
            'checkin' => $checkin,
            'orden' => $checkin->orden,
            'fotos' => collect($checkin->fotos ?? [])->map(fn ($f) => Media::urlTemporal($f, 120))->filter()->values(),
            'urlEnvio' => $this->urlFirmadaActual(request()),
        ]);
    }

    public function guardar(Request $request, Checkin $checkin, FirmarCheckin $firmar): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:160'],
            'firma' => ['required', 'string', 'max:1400000'],
            'acepta_aviso_privacidad' => ['accepted'],
            'acepta_condiciones' => ['accepted'],
        ], [], [
            'nombre' => 'nombre de quien firma',
            'acepta_aviso_privacidad' => 'el aviso de privacidad',
            'acepta_condiciones' => 'las condiciones del servicio',
        ]);

        $firmar($checkin, $datos['firma'], $datos['nombre'], true, true, 'enlace', $request->ip(), $request->userAgent());

        return redirect()->route('firma.gracias', ['folio' => $checkin->orden->folio]);
    }

    public function gracias(Request $request): View
    {
        return view('firma.gracias', ['folio' => $request->query('folio')]);
    }

    private function urlFirmadaActual(Request $request): string
    {
        // El formulario envía a la misma URL firmada (con su firma y expiración).
        return $request->fullUrl();
    }
}
