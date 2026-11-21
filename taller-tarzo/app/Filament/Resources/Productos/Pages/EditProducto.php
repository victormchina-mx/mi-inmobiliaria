<?php

namespace App\Filament\Resources\Productos\Pages;

use App\Filament\Actions\AccionesInventario;
use App\Filament\Resources\Productos\ProductoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProducto extends EditRecord
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesInventario::entrada()->after(fn () => $this->refreshFormData(['existencia'])),
            AccionesInventario::salida(),
            AccionesInventario::devolucion(),
            AccionesInventario::ajuste(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->visible(fn () => auth()->user()?->can('almacen.gestionar'));
    }
}
