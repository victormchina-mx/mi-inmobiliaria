<?php

namespace App\Filament\Resources\Flotillas\Pages;

use App\Filament\Resources\Flotillas\FlotillaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFlotillas extends ListRecords
{
    protected static string $resource = FlotillaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
