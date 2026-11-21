<?php

namespace App\Filament\Resources\Bahias\Tables;

use App\Enums\TipoBahia;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BahiasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('area.nombre')->label('Área')->sortable(),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->filters([SelectFilter::make('tipo')->label('Tipo')->options(TipoBahia::class)])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
