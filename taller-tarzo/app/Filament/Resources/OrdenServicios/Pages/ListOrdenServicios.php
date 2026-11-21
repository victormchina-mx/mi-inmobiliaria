<?php

namespace App\Filament\Resources\OrdenServicios\Pages;

use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdenServicios extends ListRecords
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Recibir vehículo')->icon('heroicon-o-plus'),
        ];
    }
}
