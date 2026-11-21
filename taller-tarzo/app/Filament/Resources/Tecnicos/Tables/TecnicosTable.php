<?php

namespace App\Filament\Resources\Tecnicos\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TecnicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('area.nombre')->label('Área')->sortable(),
                TextColumn::make('especialidad')->label('Especialidad')->toggleable(),
                TextColumn::make('costo_hora')->label('Costo/hora')->money('MXN')->sortable(),
                TextColumn::make('capacidad_horas_dia')->label('Horas/día'),
                IconColumn::make('user_id')->label('Con acceso')->boolean()->getStateUsing(fn ($record) => $record->user_id !== null),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('area_id')->label('Área')->relationship('area', 'nombre'),
                TrashedFilter::make(),
            ])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
