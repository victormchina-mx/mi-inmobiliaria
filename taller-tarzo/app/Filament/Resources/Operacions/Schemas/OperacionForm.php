<?php

namespace App\Filament\Resources\Operacions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OperacionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Operación')->required()->maxLength(160)->columnSpanFull(),
            Select::make('area_id')->label('Área')->relationship('area', 'nombre')->preload()->required(),
            TextInput::make('horas_estandar')->label('Horas estándar')->numeric()->minValue(0.1)->step(0.1)->required()
                ->helperText('Tiempo de referencia; el valuador puede ajustarlo en cada cotización.'),
            Toggle::make('activo')->label('Activa')->default(true),
        ]);
    }
}
