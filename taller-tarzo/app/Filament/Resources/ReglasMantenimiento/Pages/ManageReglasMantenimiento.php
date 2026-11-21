<?php

namespace App\Filament\Resources\ReglasMantenimiento\Pages;

use App\Filament\Resources\ReglasMantenimiento\ReglaMantenimientoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageReglasMantenimiento extends ManageRecords
{
    protected static string $resource = ReglaMantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
