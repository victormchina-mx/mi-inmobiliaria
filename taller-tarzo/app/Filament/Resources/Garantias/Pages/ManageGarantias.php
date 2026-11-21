<?php

namespace App\Filament\Resources\Garantias\Pages;

use App\Filament\Resources\Garantias\GarantiaResource;
use Filament\Resources\Pages\ManageRecords;

class ManageGarantias extends ManageRecords
{
    protected static string $resource = GarantiaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
