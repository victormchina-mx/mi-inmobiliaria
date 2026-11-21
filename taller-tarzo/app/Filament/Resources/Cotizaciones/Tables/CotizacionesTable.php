<?php

namespace App\Filament\Resources\Cotizaciones\Tables;

use App\Enums\EstadoCotizacion;
use App\Filament\Resources\Cotizaciones\CotizacionResource;
use App\Models\Cotizacion;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CotizacionesTable
{
    public static function configure(Table $table): Table
    {
        return self::columnas($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['orden.vehiculo', 'orden.cliente', 'aseguradora']))
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoCotizacion::class)->multiple()
                    ->default([EstadoCotizacion::Borrador->value, EstadoCotizacion::Enviada->value, EstadoCotizacion::AprobadaParcial->value, EstadoCotizacion::Aprobada->value]),
                TernaryFilter::make('seguro')->label('Tipo')->trueLabel('Seguro')->falseLabel('Particular')
                    ->queries(true: fn (Builder $query) => $query->whereNotNull('aseguradora_id'), false: fn (Builder $query) => $query->whereNull('aseguradora_id')),
                SelectFilter::make('aseguradora_id')->label('Aseguradora')->relationship('aseguradora', 'nombre'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function columnas(Table $table, bool $compacta = false): Table
    {
        return $table
            ->columns(array_values(array_filter([
                TextColumn::make('folio')->label('Folio')->searchable()->weight('bold'),
                $compacta ? null : TextColumn::make('orden.vehiculo.placas')->label('Vehículo')
                    ->formatStateUsing(fn (Cotizacion $record) => $record->orden->vehiculo->descripcion)
                    ->description(fn (Cotizacion $record) => $record->orden->cliente->nombre_completo)
                    ->searchable(),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('total')->label('Total')->money('MXN')->sortable(),
                TextColumn::make('monto_aseguradora')->label('Aseguradora')->money('MXN')->placeholder('—')
                    ->state(fn (Cotizacion $record) => $record->esSeguro() ? $record->monto_aseguradora : null),
                TextColumn::make('monto_cliente')->label('Cliente')->money('MXN'),
                TextColumn::make('enviada_at')->label('Enviada')->since()->placeholder('—')->sortable(),
                TextColumn::make('updated_at')->label('Actualizada')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])))
            ->recordUrl(fn (Cotizacion $record) => CotizacionResource::getUrl('edit', ['record' => $record]));
    }
}
