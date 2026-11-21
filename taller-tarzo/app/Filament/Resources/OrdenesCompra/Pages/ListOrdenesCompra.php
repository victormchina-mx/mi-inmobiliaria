<?php

namespace App\Filament\Resources\OrdenesCompra\Pages;

use App\Filament\Resources\OrdenesCompra\OrdenCompraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdenesCompra extends ListRecords
{
    protected static string $resource = OrdenCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva orden de compra')];
    }
}
