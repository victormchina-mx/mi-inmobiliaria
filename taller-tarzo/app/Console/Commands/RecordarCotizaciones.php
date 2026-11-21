<?php

namespace App\Console\Commands;

use App\Enums\EstadoCotizacion;
use App\Filament\Resources\Cotizaciones\CotizacionResource;
use App\Mail\PresupuestoParaAutorizar;
use App\Models\Cotizacion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Cotizaciones enviadas hace más de 24 h sin respuesta: avisa al asesor y reenvía el correo al cliente.
 */
class RecordarCotizaciones extends Command
{
    protected $signature = 'tarzo:recordar-cotizaciones {--horas=24}';

    protected $description = 'Recuerda las cotizaciones sin respuesta al asesor y al cliente.';

    public function handle(): int
    {
        $limite = now()->subHours((int) $this->option('horas'));

        $cotizaciones = Cotizacion::with(['orden.cliente', 'orden.asesor', 'autor'])
            ->where('estado', EstadoCotizacion::Enviada->value)
            ->where('enviada_at', '<=', $limite)
            ->whereNull('recordatorio_at')
            ->get();

        foreach ($cotizaciones as $cotizacion) {
            if ($asesor = $cotizacion->orden->asesor ?? $cotizacion->autor) {
                Notification::make()
                    ->title("Sin respuesta: {$cotizacion->folio}")
                    ->body("{$cotizacion->orden->cliente->nombre_completo} no ha respondido desde {$cotizacion->enviada_at->diffForHumans()}. Llama o reenvía el enlace.")
                    ->icon('heroicon-o-clock')->warning()
                    ->actions([Action::make('ver')->label('Ver cotización')->url(CotizacionResource::getUrl('edit', ['record' => $cotizacion], panel: 'taller'))])
                    ->sendToDatabase($asesor);
            }

            if (filled($cotizacion->orden->cliente->email) && ! $cotizacion->esSeguro()) {
                Mail::to($cotizacion->orden->cliente->email)->queue(new PresupuestoParaAutorizar($cotizacion, 'cliente', esRecordatorio: true));
            }

            $cotizacion->forceFill(['recordatorio_at' => now()])->saveQuietly();
        }

        $this->info("Recordatorios enviados: {$cotizaciones->count()}");

        return self::SUCCESS;
    }
}
