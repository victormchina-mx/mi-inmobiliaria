<?php

namespace App\Filament\Resources\Proveedores\Tables;

use App\Models\Proveedor;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProveedoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('ordenesCompra'))
            ->columns([
                TextColumn::make('nombre')->label('Proveedor')->searchable()->sortable()->description(fn (Proveedor $r) => $r->contacto),
                TextColumn::make('telefono')->label('Teléfono')->placeholder('—')->copyable(),
                TextColumn::make('dias_entrega')->label('Entrega')->suffix(' días'),
                TextColumn::make('ordenes_compra_count')->label('Compras'),
                TextColumn::make('cumplimiento')->label('Entregas a tiempo')->badge()
                    ->state(fn (Proveedor $r) => $r->cumplimiento())
                    ->formatStateUsing(fn ($state) => $state === null ? 'Sin historial' : $state.' %')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 90 ? 'success' : ($state >= 70 ? 'warning' : 'danger'))),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
