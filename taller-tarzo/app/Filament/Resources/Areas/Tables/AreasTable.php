<?php

namespace App\Filament\Resources\Areas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Área')->searchable()->sortable(),
                TextColumn::make('tarifa_hora')->label('Tarifa por hora')->money('MXN')->sortable(),
                TextColumn::make('tecnicos_count')->label('Técnicos')->counts('tecnicos'),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
