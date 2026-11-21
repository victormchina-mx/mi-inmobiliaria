<?php

namespace App\Filament\Resources\Paquetes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaquetesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Paquete')->searchable()->description(fn ($record) => $record->descripcion),
                TextColumn::make('partidas')->label('Partidas')->state(fn ($record) => count($record->partidas ?? [])),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
