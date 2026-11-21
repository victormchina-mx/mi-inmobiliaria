<?php

namespace App\Filament\Resources\Mensajes\Pages;

use App\Filament\Resources\Mensajes\MensajeResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMensajes extends ManageRecords
{
    protected static string $resource = MensajeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
