<?php

namespace App\Filament\Resources\Etapas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EtapasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden')->label('#'),
                TextColumn::make('nombre')->label('Etapa')->badge()->color(fn ($record) => $record->color),
                TextColumn::make('dias_max')->label('Días máx.')->placeholder('—'),
                TextColumn::make('limite_autos')->label('Límite autos')->placeholder('—'),
                IconColumn::make('notifica_cliente')->label('Avisa al cliente')->boolean(),
                TextColumn::make('ordenes_count')->label('Autos ahora')->counts('ordenes'),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->paginated(false)
            ->recordActions([EditAction::make()]);
    }
}
