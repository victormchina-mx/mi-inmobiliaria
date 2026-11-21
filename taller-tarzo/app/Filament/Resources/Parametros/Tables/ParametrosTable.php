<?php

namespace App\Filament\Resources\Parametros\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class ParametrosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description('Los valores iniciales son de ejemplo. Ajústalos a tus costos reales; los cambios aplican al recalcular cada cotización.')
            ->columns([
                TextColumn::make('descripcion')->label('Parámetro')->wrap(),
                TextInputColumn::make('valor')->label('Valor')->type('number')->step(0.01)
                    ->rules(['required', 'numeric', 'min:0'])
                    ->disabled(fn () => ! auth()->user()?->can('catalogos.gestionar')),
                TextColumn::make('unidad')->label('Unidad'),
            ])
            ->defaultSort('clave')
            ->paginated(false);
    }
}
