<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Enums\TipoPersona;
use App\Models\Cliente;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['datosFiscales', 'flotilla'])->withCount(['vehiculos', 'ordenes']))
            ->columns([
                TextColumn::make('nombre')->label('Cliente')
                    ->formatStateUsing(fn (Cliente $record) => $record->nombre_completo)
                    ->searchable(['nombre', 'apellidos'])->sortable(),
                TextColumn::make('telefono')->label('Teléfono')->searchable()->copyable(),
                TextColumn::make('email')->label('Correo')->searchable()->toggleable(),
                TextColumn::make('datosFiscales.rfc')->label('RFC')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('flotilla.nombre')->label('Flotilla')->placeholder('—')->toggleable(),
                TextColumn::make('vehiculos_count')->label('Vehículos')->sortable(),
                TextColumn::make('ordenes_count')->label('Visitas')->sortable(),
                IconColumn::make('acepta_promociones')->label('Promos')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Alta')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_persona')->label('Tipo')->options(TipoPersona::class),
                SelectFilter::make('flotilla_id')->label('Flotilla')->relationship('flotilla', 'nombre'),
                TernaryFilter::make('con_rfc')->label('Con datos fiscales')
                    ->queries(
                        true: fn (Builder $q) => $q->has('datosFiscales'),
                        false: fn (Builder $q) => $q->doesntHave('datosFiscales'),
                    ),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
