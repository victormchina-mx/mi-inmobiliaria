<?php

namespace App\Filament\Resources\Etapas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EtapaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre visible')->required()->maxLength(80),
            TextInput::make('clave')->label('Clave interna')
                ->helperText('Identificador usado por las reglas del sistema. No se puede cambiar después de crearla.')
                ->required()->alphaDash()->maxLength(40)->unique(ignoreRecord: true)
                ->disabledOn('edit'),
            TextInput::make('orden')->label('Posición en el tablero')->numeric()->minValue(1)->required(),
            Select::make('color')->label('Color')->options([
                'gray' => 'Gris', 'info' => 'Azul', 'primary' => 'Verde', 'warning' => 'Ámbar', 'success' => 'Verde claro', 'danger' => 'Rojo',
            ])->default('gray')->required(),
            TextInput::make('dias_max')->label('Días máximos en esta etapa')->helperText('Pasado este límite la tarjeta se marca en rojo.')->numeric()->minValue(1),
            TextInput::make('limite_autos')->label('Límite de autos simultáneos')->numeric()->minValue(1),
            Toggle::make('notifica_cliente')->label('Avisar al cliente al entrar a esta etapa'),
            Toggle::make('es_final')->label('Etapa final (orden terminada)'),
            Toggle::make('requiere_cotizacion_aprobada')->label('Solo entra con cotización autorizada'),
            Select::make('requiere_checklist')->label('Para entrar se necesita')
                ->options(['calidad' => 'Control de calidad aprobado', 'entrega' => 'Checklist de entrega firmado'])->placeholder('Nada'),
            Toggle::make('requiere_evidencia')->label('Exigir foto de evidencia para avanzar (órdenes de seguro)'),
        ]);
    }
}
