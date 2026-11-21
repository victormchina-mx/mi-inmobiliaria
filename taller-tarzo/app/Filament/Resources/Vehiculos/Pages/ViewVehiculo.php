<?php

namespace App\Filament\Resources\Vehiculos\Pages;

use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Filament\Resources\Vehiculos\Schemas\VehiculoForm;
use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Models\Vehiculo;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewVehiculo extends ViewRecord
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevaOrden')->label('Recibir vehículo')->icon('heroicon-o-clipboard-document-check')
                ->visible(fn () => auth()->user()?->can('recepcion.gestionar'))
                ->url(fn () => OrdenServicioResource::getUrl('create', ['vehiculo' => $this->record->id])),
            EditAction::make(),
            Action::make('transferir')->label('Cambiar de dueño')->icon('heroicon-o-arrows-right-left')->color('gray')
                ->visible(fn () => auth()->user()?->can('recepcion.gestionar'))
                ->modalDescription('El vehículo conserva su historial de órdenes; las órdenes anteriores siguen a nombre del dueño anterior.')
                ->schema(fn (Schema $schema) => $schema->components([VehiculoForm::selectCliente()->label('Nuevo dueño')]))
                ->action(function (array $data, Vehiculo $record): void {
                    $anterior = $record->cliente->nombre_completo;
                    $record->update(['cliente_id' => $data['cliente_id']]);
                    activity()->performedOn($record)->withProperties(['anterior' => $anterior])->log('Cambio de dueño');
                    Notification::make()->title('Dueño actualizado')->success()->send();
                }),
        ];
    }
}
