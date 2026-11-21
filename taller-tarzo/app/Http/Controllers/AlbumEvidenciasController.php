<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Álbum PDF de evidencias de la orden (todas, o solo las marcadas como visibles al cliente).
 */
class AlbumEvidenciasController extends Controller
{
    public function __invoke(Request $request, OrdenServicio $orden): Response
    {
        abort_unless($request->user()?->can('piso.ver') || $request->user()?->can('recepcion.ver'), 403);

        $soloCliente = $request->boolean('cliente');
        $evidencias = $orden->evidencias()->with('etapa')
            ->when($soloCliente, fn ($q) => $q->where('visible_cliente', true))
            ->reorder('created_at')->limit(60)->get();

        return Pdf::loadView('pdf.album-evidencias', compact('orden', 'evidencias', 'soloCliente'))
            ->setPaper('letter')->setOption('isRemoteEnabled', false)
            ->stream("evidencias-{$orden->folio}.pdf");
    }
}
