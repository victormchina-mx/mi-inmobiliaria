<?php

namespace App\Filament\Resources\Proveedores\Schemas;

use App\Rules\RfcValido;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProveedorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(160),
            TextInput::make('rfc')->label('RFC')->maxLength(13)->rule(new RfcValido),
            TextInput::make('contacto')->label('Contacto')->maxLength(160),
            TextInput::make('telefono')->label('Teléfono / WhatsApp')->tel()->telRegex('/^[+\d\s().\-]{7,20}$/')->maxLength(20),
            TextInput::make('email')->label('Correo para pedidos')->email(),
            TextInput::make('dias_entrega')->label('Días de entrega habituales')->numeric()->minValue(0)->default(2)->required(),
            Toggle::make('activo')->label('Activo')->default(true),
            Textarea::make('notas')->label('Notas (marcas, condiciones, horarios)')->rows(2)->columnSpanFull(),
        ]);
    }
}
