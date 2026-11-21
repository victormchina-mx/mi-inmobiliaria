<?php

namespace App\Http\Controllers;

use App\Support\DatosTablero;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tablero para una pantalla del taller: sin menús, letra grande y refresco automático.
 */
class TableroTvController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->can('piso.ver'), 403);

        return view('tablero.tv', [
            'columnas' => DatosTablero::columnas(),
            'refresco' => (int) filter_var(config('tarzo.tiempo_real.intervalo_polling', '15s'), FILTER_SANITIZE_NUMBER_INT) ?: 15,
        ]);
    }
}
