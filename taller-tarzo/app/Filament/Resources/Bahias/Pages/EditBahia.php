<?php

namespace App\Filament\Resources\Bahias\Pages;

use App\Filament\Resources\Bahias\BahiaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBahia extends EditRecord
{
    protected static string $resource = BahiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
