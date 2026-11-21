<?php

namespace App\Filament\Resources\Etapas\Pages;

use App\Filament\Resources\Etapas\EtapaResource;
use Filament\Resources\Pages\EditRecord;

class EditEtapa extends EditRecord
{
    protected static string $resource = EtapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
