<?php

namespace App\Filament\Resources\Tecnicos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TecnicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(120),
            Select::make('area_id')->label('Área principal')->relationship('area', 'nombre')->preload()->searchable(),
            TextInput::make('especialidad')->label('Especialidad')->placeholder('Motor, suspensión, laminado, color…')->maxLength(120),
            Select::make('user_id')->label('Usuario del sistema')
                ->helperText('Necesario para que el técnico vea sus tareas en el celular.')
                ->relationship('user', 'name')->searchable()->preload()->unique(ignoreRecord: true),
            TextInput::make('costo_hora')->label('Costo por hora')
                ->helperText('Sueldo + cargas sociales ÷ horas productivas del mes. Se usa para la utilidad por orden.')
                ->numeric()->prefix('$')->minValue(0)->default(0)->required(),
            TextInput::make('capacidad_horas_dia')->label('Capacidad (horas por día)')->numeric()->minValue(0)->maxValue(24)->default(8)->required(),
            Toggle::make('activo')->label('Activo')->default(true),
        ]);
    }
}
