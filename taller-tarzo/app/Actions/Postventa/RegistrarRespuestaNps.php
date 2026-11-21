<?php

namespace App\Actions\Postventa;

use App\Filament\Pages\Satisfaccion;
use App\Models\EncuestaNps;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Guarda la calificación. Un detractor (0–6) genera un aviso de seguimiento para la gerencia.
 */
class RegistrarRespuestaNps
{
    public function __invoke(EncuestaNps $encuesta, int $calificacion, ?string $comentario = null): EncuestaNps
    {
        if ($encuesta->respondida_at) {
            throw ValidationException::withMessages(['calificacion' => 'Esta encuesta ya fue respondida. ¡Gracias!']);
        }

        $encuesta->update(['calificacion' => $calificacion, 'comentario' => $comentario, 'respondida_at' => now()]);

        if ($encuesta->grupo() === 'detractor') {
            $encuesta->loadMissing('orden', 'cliente');
            Avisos::a('crm.gestionar', Notification::make()
                ->title("Cliente insatisfecho: {$encuesta->cliente->nombre_completo} ({$calificacion}/10)")
                ->body(($comentario ? '"'.Str::limit($comentario, 140).'" · ' : '')."Orden {$encuesta->orden->folio}. Llama para darle seguimiento.")
                ->icon('heroicon-o-face-frown')->danger()
                ->actions([Action::make('ver')->label('Dar seguimiento')->url(Satisfaccion::getUrl(panel: 'taller'))]));
        }

        return $encuesta;
    }
}
