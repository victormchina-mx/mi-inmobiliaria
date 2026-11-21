<?php

namespace App\Filament\Resources\PiezaCarrocerias\Pages;

use App\Filament\Resources\PiezaCarrocerias\PiezaCarroceriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPiezaCarrocerias extends ListRecords
{
    protected static string $resource = PiezaCarroceriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
