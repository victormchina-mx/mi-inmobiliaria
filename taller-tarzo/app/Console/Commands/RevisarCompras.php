<?php

namespace App\Console\Commands;

use App\Filament\Resources\OrdenesCompra\OrdenCompraResource;
use App\Models\OrdenCompra;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

/**
 * Avisa a almacén de las órdenes de compra que pasaron su fecha estimada (una vez al día por orden).
 */
class RevisarCompras extends Command
{
    protected $signature = 'tarzo:revisar-compras';

    protected $description = 'Avisa de órdenes de compra atrasadas.';

    public function handle(): int
    {
        $atrasadas = OrdenCompra::with('proveedor')
            ->whereNotIn('estado', ['borrador', 'recibida', 'cancelada'])
            ->whereDate('fecha_estimada', '<', today())
            ->where(fn ($q) => $q->whereNull('alerta_retraso_at')->orWhereDate('alerta_retraso_at', '<', today()))
            ->get();

        foreach ($atrasadas as $oc) {
            Avisos::a('almacen.gestionar', Notification::make()
                ->title("Compra atrasada: {$oc->folio}")
                ->body("{$oc->proveedor->nombre} debía entregar el {$oc->fecha_estimada->format('d/m')} ({$oc->fecha_estimada->diffForHumans()}).")
                ->icon('heroicon-o-truck')->danger()
                ->actions([Action::make('ver')->label('Ver orden de compra')->url(OrdenCompraResource::getUrl('edit', ['record' => $oc], panel: 'taller'))]));

            $oc->forceFill(['alerta_retraso_at' => now()])->saveQuietly();
        }

        $this->info("Compras atrasadas avisadas: {$atrasadas->count()}");

        return self::SUCCESS;
    }
}
