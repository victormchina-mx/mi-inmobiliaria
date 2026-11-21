<?php

namespace App\Filament\Resources\Etapas\Pages;

use App\Filament\Resources\Etapas\EtapaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEtapas extends ListRecords
{
    protected static string $resource = EtapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
