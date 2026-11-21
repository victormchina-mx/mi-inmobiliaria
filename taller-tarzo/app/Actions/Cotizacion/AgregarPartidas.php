<?php

namespace App\Actions\Cotizacion;

use App\Enums\Paga;
use App\Enums\Prioridad;
use App\Enums\TipoPartida;
use App\Models\Area;
use App\Models\Cotizacion;
use App\Models\Diagnostico;
use App\Models\Paquete;
use App\Services\Cotizacion\Cotizador;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Agrega partidas a un borrador desde un paquete de servicio o desde hallazgos de diagnóstico.
 */
class AgregarPartidas
{
    public function desdePaquete(Cotizacion $cotizacion, Paquete $paquete): Cotizacion
    {
        $this->exigirBorrador($cotizacion);

        foreach ($paquete->partidas as $plantilla) {
            $this->crear($cotizacion, $plantilla);
        }

        return app(Cotizador::class)->recalcular($cotizacion);
    }

    /**
     * @param  Collection<int, Diagnostico>  $diagnosticos
     */
    public function desdeDiagnosticos(Cotizacion $cotizacion, Collection $diagnosticos): Cotizacion
    {
        $this->exigirBorrador($cotizacion);

        // Lo urgente primero.
        $orden = [Prioridad::Urgente->value => 0, Prioridad::Recomendado->value => 1, Prioridad::PuedeEsperar->value => 2];

        $areaPorDefecto = Area::where('clave', 'mecanica')->value('id');

        foreach ($diagnosticos->where('convertido_a_partida', false)->sortBy(fn (Diagnostico $d) => $orden[$d->prioridad->value]) as $d) {
            $this->crear($cotizacion, [
                'tipo' => TipoPartida::ManoObra->value,
                'descripcion' => $d->hallazgo.($d->prioridad === Prioridad::Urgente ? ' (urgente)' : ''),
                'area_id' => $d->tecnico?->area_id ?? $areaPorDefecto,
                'horas' => 1,
                'diagnostico_id' => $d->id,
            ]);
            $d->update(['convertido_a_partida' => true]);
        }

        return app(Cotizador::class)->recalcular($cotizacion);
    }

    private function crear(Cotizacion $cotizacion, array $datos): void
    {
        $cotizacion->partidas()->create([
            'paga' => $cotizacion->esSeguro() ? Paga::Aseguradora->value : Paga::Cliente->value,
            'orden' => (int) $cotizacion->partidas()->max('orden') + 1,
            'cantidad' => 1,
            ...$datos,
        ]);
    }

    private function exigirBorrador(Cotizacion $cotizacion): void
    {
        if (! $cotizacion->estado->editable()) {
            throw ValidationException::withMessages(['cotizacion' => 'Solo se pueden agregar partidas a un borrador. Crea un suplemento.']);
        }
    }
}
