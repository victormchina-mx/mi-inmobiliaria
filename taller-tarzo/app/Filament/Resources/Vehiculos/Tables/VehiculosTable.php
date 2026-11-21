<?php

namespace App\Filament\Resources\Vehiculos\Tables;

use App\Models\Vehiculo;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehiculosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('cliente')->withCount('ordenes'))
            ->columns([
                TextColumn::make('placas')->label('Placas')->searchable()->placeholder('—')->weight('bold'),
                TextColumn::make('marca')->label('Vehículo')
                    ->formatStateUsing(fn ($record) => "{$record->marca} {$record->modelo} {$record->anio}")
                    ->searchable(['marca', 'modelo'])->sortable(),
                TextColumn::make('cliente.nombre')->label('Dueño')
                    ->formatStateUsing(fn ($record) => $record->cliente?->nombre_completo)
                    ->searchable(['nombre', 'apellidos']),
                TextColumn::make('vin')->label('VIN')->searchable()->placeholder('—')->fontFamily('mono')->toggleable(),
                TextColumn::make('color')->label('Color')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kilometraje')->label('Km')->numeric()->sortable()->toggleable(),
                TextColumn::make('ordenes_count')->label('Visitas')->sortable(),
            ])
            ->filters([
                SelectFilter::make('marca')->label('Marca')
                    ->options(fn () => Vehiculo::query()->distinct()->orderBy('marca')->pluck('marca', 'marca')->all()),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
