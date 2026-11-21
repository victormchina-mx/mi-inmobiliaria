<?php

namespace App\Filament\Resources\Tecnicos\Schemas;

use App\Models\Tecnico;
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
            TextInput::make('sueldo_mensual')->label('Sueldo mensual')->numeric()->prefix('$')->minValue(0)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $get, $set) => self::costo($get, $set)),
            TextInput::make('factor_cargas')->label('Factor de cargas sociales')->numeric()->step(0.01)->minValue(1)->default(1.35)
                ->helperText('IMSS, Infonavit, ISN, aguinaldo y vacaciones. 1.35 = 35 % sobre el sueldo.')
                ->live(onBlur: true)->afterStateUpdated(fn ($state, $get, $set) => self::costo($get, $set)),
            TextInput::make('horas_productivas_mes')->label('Horas productivas al mes')->numeric()->minValue(1)->default(192)
                ->live(onBlur: true)->afterStateUpdated(fn ($state, $get, $set) => self::costo($get, $set)),
            TextInput::make('costo_hora')->label('Costo de hora-hombre')
                ->helperText('Se calcula: sueldo × factor ÷ horas productivas. Se usa para la utilidad por orden.')
                ->numeric()->prefix('$')->minValue(0)->default(0)->required(),
            TextInput::make('capacidad_horas_dia')->label('Capacidad (horas por día)')->numeric()->minValue(0)->maxValue(24)->default(8)->required(),
            Toggle::make('activo')->label('Activo')->default(true),
        ]);
    }

    private static function costo($get, $set): void
    {
        $c = Tecnico::calcularCostoHora((float) $get('sueldo_mensual'), (float) $get('factor_cargas'), (int) $get('horas_productivas_mes'));
        if ($c !== null) {
            $set('costo_hora', $c);
        }
    }
}
