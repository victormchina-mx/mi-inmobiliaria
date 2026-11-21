<?php

namespace App\Actions\Almacen;

use App\Enums\Disponibilidad;
use App\Enums\EstadoPartida;
use App\Enums\OperacionHyP;
use App\Enums\TipoPartida;
use App\Models\Cotizacion;
use App\Models\Requisicion;

/**
 * Crea una requisición por cada refacción autorizada que no está en almacén
 * (incluye piezas nuevas de hojalatería). Es idempotente.
 */
class CrearRequisiciones
{
    public function __invoke(Cotizacion $cotizacion): int
    {
        $nuevas = 0;

        $partidas = $cotizacion->partidas()->where('estado', EstadoPartida::Aprobada->value)->get()
            ->filter(fn ($p) => ($p->tipo === TipoPartida::Refaccion && $p->disponibilidad !== Disponibilidad::EnStock)
                || ($p->tipo === TipoPartida::HojalateriaPintura && $p->operacion_hyp === OperacionHyP::Sustituir));

        foreach ($partidas as $p) {
            $r = Requisicion::firstOrCreate(['partida_id' => $p->id], [
                'orden_servicio_id' => $cotizacion->orden_servicio_id,
                'descripcion' => $p->descripcion,
                'numero_parte' => $p->numero_parte,
                'cantidad' => $p->cantidad ?: 1,
                'surte' => 'taller',
            ]);
            $r->wasRecentlyCreated && $nuevas++;
        }

        return $nuevas;
    }
}
