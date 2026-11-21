<?php

namespace App\Filament\Resources\Productos\Pages;

use App\Filament\Pages\ConteoInventario;
use App\Filament\Resources\Productos\ProductoResource;
use App\Filament\Resources\Productos\Widgets\ResumenInventario;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderWidgets(): array
    {
        return [ResumenInventario::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('conteo')->label('Conteo físico')->icon('heroicon-o-clipboard-document-check')->color('gray')
                ->visible(fn () => auth()->user()?->can('almacen.gestionar'))
                ->url(ConteoInventario::getUrl()),
            CreateAction::make()->label('Nuevo producto'),
        ];
    }
}
