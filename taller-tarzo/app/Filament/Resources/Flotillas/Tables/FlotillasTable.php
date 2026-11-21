<?php

namespace App\Filament\Resources\Flotillas\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlotillasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('clientes'))
            ->columns([
                TextColumn::make('nombre')->label('Flotilla')->searchable()->sortable(),
                TextColumn::make('clientes_count')->label('Clientes'),
                TextColumn::make('dias_credito')->label('Crédito')->suffix(' días'),
                TextColumn::make('limite_credito')->label('Límite')->money('MXN'),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->defaultSort('nombre')
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
