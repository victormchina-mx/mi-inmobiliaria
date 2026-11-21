<?php

namespace App\Filament\Resources\PlantillasMensaje\Pages;

use App\Filament\Resources\PlantillasMensaje\PlantillaMensajeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePlantillasMensaje extends ManageRecords
{
    protected static string $resource = PlantillaMensajeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
