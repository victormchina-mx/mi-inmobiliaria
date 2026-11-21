<?php

namespace App\Http\Controllers;

use App\Actions\Cotizacion\EnlaceAprobacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Enums\EstadoPartida;
use App\Models\Cotizacion;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Página pública (enlace firmado) donde el cliente o el ajustador autorizan partidas.
 */
class AprobacionPresupuestoController extends Controller
{
    public function mostrar(Request $request, Cotizacion $cotizacion, string $rol): View
    {
        abort_unless(in_array($rol, EnlaceAprobacion::ROLES, true), 404);

        $cotizacion->load(['partidas.diagnostico', 'partidas.pieza', 'orden.cliente', 'orden.vehiculo', 'orden.siniestro', 'aseguradora']);
        $mias = ResponderCotizacion::partidasDelRol($cotizacion, $rol);

        return view('presupuesto.mostrar', [
            'cotizacion' => $cotizacion,
            'orden' => $cotizacion->orden,
            'rol' => $rol,
            'mias' => $mias,
            'pendientes' => $mias->where('estado', EstadoPartida::Pendiente),
            'otras' => $cotizacion->partidas->reject(fn ($p) => $mias->contains('id', $p->id))->values(),
            'fotos' => fn ($partida) => collect($partida->diagnostico?->fotos ?? [])->take(4)->map(fn ($f) => Media::urlTemporal($f, 120))->filter(),
            'urlEnvio' => $request->fullUrl(),
        ]);
    }

    public function guardar(Request $request, Cotizacion $cotizacion, string $rol, ResponderCotizacion $responder): RedirectResponse
    {
        abort_unless(in_array($rol, EnlaceAprobacion::ROLES, true), 404);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:160'],
            'decision' => ['array'],
            'decision.*' => ['in:aprobada,rechazada'],
            'comentario' => ['nullable', 'string', 'max:1000'],
            'acepta' => ['accepted'],
        ], [], ['nombre' => 'nombre de quien autoriza', 'acepta' => 'la confirmación']);

        $responder($cotizacion, $rol, $datos['decision'] ?? [], $datos['nombre'], $datos['comentario'] ?? null, 'enlace', $request->ip(), $request->userAgent());

        return redirect($request->fullUrl())->with('respondido', true);
    }
}
