<?php

namespace App\Filament\Resources\OrdenServicios\Pages;

use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use Filament\Resources\Pages\EditRecord;

class EditOrdenServicio extends EditRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getRedirectUrl(): string
    {
        return OrdenServicioResource::getUrl('view', ['record' => $this->record]);
    }
}
