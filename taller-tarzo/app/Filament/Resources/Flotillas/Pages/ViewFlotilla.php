<?php

namespace App\Filament\Resources\Flotillas\Pages;

use App\Filament\Resources\Flotillas\FlotillaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFlotilla extends ViewRecord
{
    protected static string $resource = FlotillaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
