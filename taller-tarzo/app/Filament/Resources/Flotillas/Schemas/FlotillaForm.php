<?php

namespace App\Filament\Resources\Flotillas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FlotillaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Flotilla')->columns(2)->schema([
                TextInput::make('nombre')->label('Nombre de la empresa o flotilla')->required()->maxLength(160)->columnSpanFull(),
                TextInput::make('dias_credito')->label('Días de crédito')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('limite_credito')->label('Límite de crédito')->numeric()->prefix('$')->minValue(0)->default(0)->required(),
                TextInput::make('descuento_mano_obra')->label('Descuento en mano de obra')->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0),
                TextInput::make('descuento_refacciones')->label('Descuento en refacciones')->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0),
                Toggle::make('activo')->label('Activa')->default(true),
                Textarea::make('notas')->label('Condiciones del convenio')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }
}
