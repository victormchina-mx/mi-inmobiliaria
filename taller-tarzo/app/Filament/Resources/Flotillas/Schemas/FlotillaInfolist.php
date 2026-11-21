<?php

namespace App\Filament\Resources\Flotillas\Schemas;

use App\Models\Flotilla;
use App\Models\Vehiculo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FlotillaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Convenio')->columns(4)->schema([
                TextEntry::make('nombre')->label('Flotilla')->columnSpan(2),
                TextEntry::make('dias_credito')->label('Crédito')->suffix(' días'),
                TextEntry::make('limite_credito')->label('Límite de crédito')->money('MXN'),
                TextEntry::make('descuento_mano_obra')->label('Desc. mano de obra')->suffix('%'),
                TextEntry::make('descuento_refacciones')->label('Desc. refacciones')->suffix('%'),
                TextEntry::make('vehiculos')->label('Vehículos')
                    ->state(fn (Flotilla $record) => Vehiculo::whereIn('cliente_id', $record->clientes()->select('id'))->count()),
                TextEntry::make('notas')->label('Condiciones')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }
}
