<?php

namespace App\Filament\Resources\Aseguradoras\Schemas;

use App\Rules\RfcValido;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AseguradoraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre comercial')->required()->maxLength(120)->unique(ignoreRecord: true),
            TextInput::make('rfc')->label('RFC')->maxLength(13)->rule(new RfcValido),
            TextInput::make('telefono')->label('Teléfono de siniestros')->tel()->maxLength(20),
            TextInput::make('email')->label('Correo para facturas y valuaciones')->email(),
            TextInput::make('dias_pago')->label('Días de pago')->helperText('Plazo habitual entre la factura y el pago.')->numeric()->minValue(0)->default(30)->required(),
            Toggle::make('activo')->label('Activa')->default(true),
            Textarea::make('notas')->label('Notas')->rows(3)->columnSpanFull(),
        ]);
    }
}
