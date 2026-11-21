<?php

namespace App\Filament\Resources\Paquetes\Schemas;

use App\Enums\TipoPartida;
use App\Models\Area;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaqueteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre del paquete')->required()->maxLength(160),
            TextInput::make('descripcion')->label('Descripción')->maxLength(255),
            Toggle::make('activo')->label('Activo')->default(true),
            Repeater::make('partidas')->label('Partidas del paquete')
                ->schema([
                    Select::make('tipo')->label('Tipo')->options([
                        TipoPartida::ManoObra->value => TipoPartida::ManoObra->getLabel(),
                        TipoPartida::Refaccion->value => TipoPartida::Refaccion->getLabel(),
                        TipoPartida::Otro->value => TipoPartida::Otro->getLabel(),
                    ])->required()->live(),
                    TextInput::make('descripcion')->label('Concepto')->required()->columnSpan(2),
                    Select::make('area_id')->label('Área')
                        ->options(fn () => Area::pluck('nombre', 'id'))
                        ->visible(fn (Get $get) => $get('tipo') === TipoPartida::ManoObra->value),
                    TextInput::make('horas')->label('Horas')->numeric()->visible(fn (Get $get) => $get('tipo') === TipoPartida::ManoObra->value),
                    TextInput::make('cantidad')->label('Cantidad')->numeric()->default(1)->visible(fn (Get $get) => $get('tipo') !== TipoPartida::ManoObra->value),
                    TextInput::make('costo_unitario')->label('Costo unitario')->numeric()->prefix('$')->visible(fn (Get $get) => $get('tipo') !== TipoPartida::ManoObra->value),
                ])
                ->columns(4)->columnSpanFull()->addActionLabel('Agregar partida')->required(),
        ]);
    }
}
