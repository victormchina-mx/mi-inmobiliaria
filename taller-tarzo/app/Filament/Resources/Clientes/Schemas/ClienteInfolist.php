<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Models\Cliente;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')->columns(4)->schema([
                TextEntry::make('nombre_completo')->label('Cliente'),
                TextEntry::make('tipo_persona')->label('Tipo')->badge(),
                TextEntry::make('telefono')->label('Teléfono')->placeholder('—')->copyable(),
                TextEntry::make('email')->label('Correo')->placeholder('—')->copyable(),
                TextEntry::make('visitas')->label('Visitas')
                    ->state(fn (Cliente $record) => $record->ordenes()->count()),
                TextEntry::make('ultima_visita')->label('Última visita')
                    ->state(fn (Cliente $record) => $record->ordenes()->max('fecha_ingreso'))
                    ->since()->placeholder('Sin visitas'),
                TextEntry::make('vehiculos_count')->label('Vehículos')
                    ->state(fn (Cliente $record) => $record->vehiculos()->count()),
                TextEntry::make('flotilla.nombre')->label('Flotilla')->placeholder('—'),
                TextEntry::make('canal_preferido')->label('Canal preferido')->badge(),
                TextEntry::make('datosFiscales.rfc')->label('RFC')->placeholder('Sin datos fiscales'),
                TextEntry::make('notas')->label('Notas y preferencias')->placeholder('—')->columnSpan(2),
            ]),
        ]);
    }
}
