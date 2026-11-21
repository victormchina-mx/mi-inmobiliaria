<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\Vehiculo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehiculoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Expediente del vehículo')->columns(4)->schema([
                TextEntry::make('descripcion')->label('Vehículo')->columnSpan(2),
                TextEntry::make('cliente.nombre_completo')->label('Dueño')
                    ->url(fn (Vehiculo $record) => ClienteResource::getUrl('view', ['record' => $record->cliente_id])),
                TextEntry::make('cliente.telefono')->label('Teléfono')->placeholder('—')->copyable(),
                TextEntry::make('vin')->label('VIN')->placeholder('—')->copyable()->fontFamily('mono'),
                TextEntry::make('placas')->label('Placas')->placeholder('—'),
                TextEntry::make('color')->label('Color')->placeholder('—'),
                TextEntry::make('kilometraje')->label('Último kilometraje')->numeric()->suffix(' km')->placeholder('—'),
                TextEntry::make('motor')->label('Motor')->placeholder('—'),
                TextEntry::make('transmision')->label('Transmisión')->placeholder('—'),
                TextEntry::make('version')->label('Versión')->placeholder('—'),
                TextEntry::make('poliza_vigente')->label('Póliza vigente')
                    ->state(function (Vehiculo $record) {
                        $poliza = $record->polizas()->with('aseguradora')->latest('vigencia_fin')->first();

                        return $poliza ? "{$poliza->aseguradora->nombre} · {$poliza->numero}" : null;
                    })
                    ->color(fn (Vehiculo $record) => $record->polizas()->latest('vigencia_fin')->first()?->estaVigente() === false ? 'danger' : null)
                    ->placeholder('Sin póliza registrada'),
                TextEntry::make('notas')->label('Notas')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }
}
