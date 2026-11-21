<?php

namespace App\Actions\Piso;

use App\Actions\Ordenes\CambiarEtapa;
use App\Enums\Disponibilidad;
use App\Enums\EstadoPartida;
use App\Enums\TipoPartida;
use App\Models\Cotizacion;
use App\Models\Etapa;

/**
 * Lo que pasa en el piso cuando se autorizan partidas:
 *  1. Se generan las tareas de taller.
 *  2. Si hay refacciones autorizadas que no están en almacén y el auto sigue en
 *     recepción o diagnóstico, pasa solo a "Espera de refacciones".
 */
class TrasAutorizacion
{
    public function __invoke(Cotizacion $cotizacion): void
    {
        app(GenerarTareas::class)($cotizacion);

        $orden = $cotizacion->orden()->with('etapa')->first();

        if (! in_array($orden->etapa->clave, [Etapa::RECEPCION, 'diagnostico'], true)) {
            return;
        }

        $faltanRefacciones = $cotizacion->partidas()
            ->where('estado', EstadoPartida::Aprobada->value)
            ->whereIn('tipo', [TipoPartida::Refaccion->value, TipoPartida::HojalateriaPintura->value])
            ->whereIn('disponibilidad', [Disponibilidad::PorPedir->value, Disponibilidad::PorConfirmar->value])
            ->exists();

        $espera = Etapa::firstWhere('clave', Etapa::ESPERA_REFACCIONES);

        if ($faltanRefacciones && $espera) {
            (new CambiarEtapa)($orden, $espera, null, 'Automático: refacciones autorizadas por pedir');
        }
    }
}
