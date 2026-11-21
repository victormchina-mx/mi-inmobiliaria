<?php

namespace App\Filament\Resources\PiezaCarrocerias\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PiezaCarroceriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Pieza')->required()->maxLength(120)->unique(ignoreRecord: true)->columnSpanFull(),
            TextInput::make('superficie_m2')->label('Superficie')->numeric()->minValue(0)->step(0.05)->suffix('m²')->required()
                ->helperText('Base para calcular materiales de pintura.'),
            TextInput::make('horas_laminado_ref')->label('Horas de laminado (daño medio)')->numeric()->minValue(0)->step(0.1),
            TextInput::make('horas_pintura_ref')->label('Horas de pintura')->numeric()->minValue(0)->step(0.1),
            Toggle::make('activo')->label('Activa')->default(true),
        ]);
    }
}
