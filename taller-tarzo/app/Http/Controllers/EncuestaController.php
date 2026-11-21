<?php

namespace App\Http\Controllers;

use App\Actions\Postventa\RegistrarRespuestaNps;
use App\Models\EncuestaNps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EncuestaController extends Controller
{
    public function mostrar(EncuestaNps $encuesta): View
    {
        return view('portal.encuesta', ['encuesta' => $encuesta->load('orden.vehiculo', 'cliente'), 'urlResena' => config('tarzo.url_resena')]);
    }

    public function guardar(Request $request, EncuestaNps $encuesta, RegistrarRespuestaNps $registrar): RedirectResponse
    {
        $datos = $request->validate([
            'calificacion' => ['required', 'integer', 'between:0,10'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ], [], ['calificacion' => 'calificación']);

        $registrar($encuesta, (int) $datos['calificacion'], $datos['comentario'] ?? null);

        return redirect($request->fullUrl());
    }
}
