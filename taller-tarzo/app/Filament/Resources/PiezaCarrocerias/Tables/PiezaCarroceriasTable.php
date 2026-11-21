<?php

namespace App\Filament\Resources\PiezaCarrocerias\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PiezaCarroceriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Pieza')->searchable()->sortable(),
                TextColumn::make('superficie_m2')->label('m²')->numeric(2)->sortable(),
                TextColumn::make('horas_laminado_ref')->label('Laminado h')->numeric(1)->placeholder('—'),
                TextColumn::make('horas_pintura_ref')->label('Pintura h')->numeric(1)->placeholder('—'),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
