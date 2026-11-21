<?php

namespace App\Filament\Resources\Aseguradoras\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AseguradorasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Aseguradora')->searchable()->sortable(),
                TextColumn::make('rfc')->label('RFC')->placeholder('—'),
                TextColumn::make('telefono')->label('Teléfono')->placeholder('—'),
                TextColumn::make('dias_pago')->label('Días de pago')->sortable(),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->defaultSort('nombre')
            ->recordActions([EditAction::make()]);
    }
}
