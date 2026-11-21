<?php

namespace App\Filament\Resources\Vehiculos\RelationManagers;

use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Filament\Resources\OrdenServicios\Tables\OrdenServiciosTable;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdenesRelationManager extends RelationManager
{
    protected static string $relationship = 'ordenes';

    protected static ?string $title = 'Historial de órdenes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return OrdenServiciosTable::historial($table)
            ->headerActions([
                Action::make('nueva')->label('Nueva orden para este vehículo')->icon('heroicon-o-plus')
                    ->visible(fn () => auth()->user()?->can('recepcion.gestionar'))
                    ->url(fn () => OrdenServicioResource::getUrl('create', ['vehiculo' => $this->getOwnerRecord()->id])),
            ]);
    }
}
