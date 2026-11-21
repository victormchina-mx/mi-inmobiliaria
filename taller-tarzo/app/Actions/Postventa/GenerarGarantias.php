<?php

namespace App\Actions\Postventa;

use App\Enums\EstadoPartida;
use App\Enums\TipoGarantia;
use App\Enums\TipoPartida;
use App\Models\Garantia;
use App\Models\OrdenServicio;
use App\Models\Parametro;

/**
 * Al entregar, crea una garantía por cada tipo de trabajo autorizado (plazos en Parámetros).
 */
class GenerarGarantias
{
    public function __invoke(OrdenServicio $orden): int
    {
        $cotizacion = $orden->cotizacionActual()->first();
        if (! $cotizacion || $orden->garantias()->exists()) {
            return 0;
        }

        $tipos = $cotizacion->partidas()->where('estado', EstadoPartida::Aprobada->value)->pluck('tipo')
            ->map(fn ($t) => $t instanceof TipoPartida ? $t : TipoPartida::from($t))
            ->map(fn (TipoPartida $t) => match ($t) {
                TipoPartida::ManoObra => TipoGarantia::Mecanica,
                TipoPartida::HojalateriaPintura => TipoGarantia::Pintura,
                TipoPartida::Refaccion => TipoGarantia::Refacciones,
                default => null,
            })->filter()->unique(fn ($t) => $t->value);

        $km = max((int) $orden->vehiculo->kilometraje, (int) $orden->kilometraje_ingreso) ?: null;
        $inicia = ($orden->fecha_entrega ?? now())->toDateString();

        foreach ($tipos as $tipo) {
            $meses = (int) Parametro::valor("garantia_{$tipo->value}_meses", 3);
            $kmGarantia = (int) Parametro::valor("garantia_{$tipo->value}_km", 0);

            Garantia::create([
                'orden_servicio_id' => $orden->id,
                'vehiculo_id' => $orden->vehiculo_id,
                'tipo' => $tipo,
                'descripcion' => "{$tipo->getLabel()} · orden {$orden->folio}",
                'inicia' => $inicia,
                'vence' => now()->parse($inicia)->addMonthsNoOverflow($meses)->toDateString(),
                'km_inicial' => $km,
                'km_limite' => $kmGarantia > 0 && $km ? $km + $kmGarantia : null,
            ]);
        }

        return $tipos->count();
    }
}
