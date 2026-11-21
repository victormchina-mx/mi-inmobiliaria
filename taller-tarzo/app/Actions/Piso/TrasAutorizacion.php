<?php

namespace App\Actions\Piso;

use App\Actions\Almacen\CrearRequisiciones;
use App\Actions\Ordenes\CambiarEtapa;
use App\Models\Cotizacion;
use App\Models\Etapa;

/**
 * Lo que pasa en el piso cuando se autorizan partidas:
 *  1. Se generan las tareas de taller y las requisiciones de refacciones.
 *  2. Si quedan piezas por conseguir y el auto sigue en recepción o diagnóstico,
 *     pasa solo a "Espera de refacciones" (regresa cuando almacén las recibe).
 */
class TrasAutorizacion
{
    public function __invoke(Cotizacion $cotizacion): void
    {
        app(GenerarTareas::class)($cotizacion);
        app(CrearRequisiciones::class)($cotizacion);

        $orden = $cotizacion->orden()->with('etapa')->first();

        if (! in_array($orden->etapa->clave, [Etapa::RECEPCION, 'diagnostico'], true)) {
            return;
        }

        $faltanRefacciones = $orden->requisiciones()->abiertas()->exists();

        $espera = Etapa::firstWhere('clave', Etapa::ESPERA_REFACCIONES);

        if ($faltanRefacciones && $espera) {
            (new CambiarEtapa)($orden, $espera, null, 'Automático: refacciones autorizadas por pedir');
        }
    }
}
