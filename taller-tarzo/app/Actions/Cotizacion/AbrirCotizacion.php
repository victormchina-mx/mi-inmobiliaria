<?php

namespace App\Actions\Cotizacion;

use App\Enums\EstadoCotizacion;
use App\Enums\TipoOrden;
use App\Models\Cotizacion;
use App\Models\OrdenServicio;
use App\Models\Parametro;
use App\Models\User;

/**
 * Devuelve el borrador vigente de la orden o crea la primera versión (C1).
 * Si la última versión ya se envió, hay que crear un suplemento (CrearSuplemento).
 */
class AbrirCotizacion
{
    public function __invoke(OrdenServicio $orden, ?User $usuario = null): Cotizacion
    {
        $actual = $orden->cotizacionActual()->first();

        if ($actual) {
            return $actual;
        }

        // Se guarda y se recarga para tener en memoria los valores por defecto de la base (IVA, totales).
        return Cotizacion::create([
            'orden_servicio_id' => $orden->id,
            'version' => 1,
            'folio' => "{$orden->folio}-C1",
            'estado' => EstadoCotizacion::Borrador,
            'aseguradora_id' => $orden->tipo === TipoOrden::Seguro ? $orden->aseguradora_id : null,
            'user_id' => $usuario?->id,
            'iva_tasa' => 0.16,
            'vigencia_dias' => (int) Parametro::valor('vigencia_cotizacion', 15),
        ])->refresh();
    }
}
