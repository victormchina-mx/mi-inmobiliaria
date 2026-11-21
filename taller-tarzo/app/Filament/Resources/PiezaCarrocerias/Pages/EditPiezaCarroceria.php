<?php

namespace App\Filament\Resources\PiezaCarrocerias\Pages;

use App\Filament\Resources\PiezaCarrocerias\PiezaCarroceriaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPiezaCarroceria extends EditRecord
{
    protected static string $resource = PiezaCarroceriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
