<?php

namespace App\Actions\Almacen;

use App\Actions\Ordenes\CambiarEtapa;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Etapa;
use App\Models\HistorialEtapa;
use App\Models\OrdenServicio;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Cuando una orden ya tiene todas sus piezas, sale de "Espera de refacciones" y regresa a la
 * etapa de donde vino; se avisa al asesor y al jefe de taller.
 */
class VerificarRefacciones
{
    public function __invoke(OrdenServicio $orden): bool
    {
        if ($orden->requisiciones()->abiertas()->exists()) {
            return false;
        }

        $orden->loadMissing('etapa');
        $movida = false;

        if ($orden->etapa->clave === Etapa::ESPERA_REFACCIONES) {
            $entrada = HistorialEtapa::where('orden_servicio_id', $orden->id)->where('etapa_id', $orden->etapa_id)->latest('id')->first();
            $destino = $entrada?->etapaAnterior;

            // Si vino de recepción o diagnóstico, ya puede pasar a reparación: se deja en diagnóstico para que el jefe la asigne.
            if (! $destino || in_array($destino->clave, [Etapa::RECEPCION], true)) {
                $destino = Etapa::firstWhere('clave', 'diagnostico');
            }

            try {
                (new CambiarEtapa)($orden, $destino, null, 'Automático: refacciones completas');
                $movida = true;
            } catch (ValidationException) {
                // Las reglas de la etapa lo impiden: se queda y el aviso lo indica.
            }
        }

        $aviso = Notification::make()
            ->title("Refacciones completas: {$orden->folio}")
            ->body($movida ? "{$orden->vehiculo->descripcion} regresó a {$orden->fresh()->etapa->nombre}." : "{$orden->vehiculo->descripcion} ya tiene todas sus piezas.")
            ->icon('heroicon-o-cube')->success()
            ->actions([Action::make('ver')->label('Ver orden')->url(OrdenServicioResource::getUrl('view', ['record' => $orden], panel: 'taller'))]);

        if ($orden->asesor) {
            $aviso->sendToDatabase($orden->asesor);
        }
        Avisos::a('piso.gestionar', $aviso);

        return $movida;
    }
}
