<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('tarifa_hora')->label('Tarifa por hora')->helperText('Precio al cliente por hora de mano de obra en esta área.')
                ->numeric()->prefix('$')->suffix('MXN')->minValue(0)->default(0)->required(),
            TextInput::make('descripcion')->label('Descripción')->maxLength(255)->columnSpanFull(),
            Toggle::make('activo')->label('Activa')->default(true),
        ]);
    }
}
