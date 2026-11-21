<?php

namespace App\Filament\Resources\Bahias\Pages;

use App\Filament\Resources\Bahias\BahiaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBahias extends ListRecords
{
    protected static string $resource = BahiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
