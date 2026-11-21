<?php

namespace App\Actions\Postventa;

use App\Filament\Resources\Garantias\GarantiaResource;
use App\Models\Garantia;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Marca garantías vencidas y avisa al asesor de las que vencen en los próximos 15 días.
 */
class RevisarGarantias
{
    public function __invoke(): int
    {
        Garantia::where('estado', 'vigente')->whereDate('vence', '<', today())->update(['estado' => 'vencida']);

        $porVencer = Garantia::with(['orden.asesor', 'vehiculo.cliente'])
            ->where('estado', 'vigente')->whereNull('alerta_at')
            ->whereBetween('vence', [today(), today()->addDays(15)])->get();

        foreach ($porVencer as $g) {
            $aviso = Notification::make()
                ->title("Garantía por vencer: {$g->vehiculo->descripcion}")
                ->body("{$g->tipo->getLabel()} de la orden {$g->orden->folio} vence el {$g->vence->format('d/m/Y')}. Buen momento para invitar al cliente a una revisión.")
                ->icon('heroicon-o-shield-exclamation')->warning()
                ->actions([Action::make('ver')->label('Ver garantías')->url(GarantiaResource::getUrl(panel: 'taller'))]);

            $g->orden->asesor ? $aviso->sendToDatabase($g->orden->asesor) : Avisos::a('crm.gestionar', $aviso);
            $g->forceFill(['alerta_at' => now()])->save();
        }

        return $porVencer->count();
    }
}
