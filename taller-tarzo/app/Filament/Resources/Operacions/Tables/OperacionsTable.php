<?php

namespace App\Filament\Resources\Operacions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OperacionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Operación')->searchable()->sortable(),
                TextColumn::make('area.nombre')->label('Área')->badge(),
                TextColumn::make('horas_estandar')->label('Horas')->numeric(1)->sortable(),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->filters([SelectFilter::make('area_id')->label('Área')->relationship('area', 'nombre')])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
