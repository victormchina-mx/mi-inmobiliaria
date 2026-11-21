<?php

namespace App\Filament\Resources\OrdenServicios\Tables;

use App\Enums\TipoOrden;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\OrdenServicio;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdenServiciosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehiculo', 'cliente', 'etapa', 'checkin', 'asesor']))
            ->columns(self::columnas())
            ->filters([
                TernaryFilter::make('abiertas')->label('Estado')
                    ->trueLabel('Abiertas')->falseLabel('Cerradas')->placeholder('Todas')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('cerrada_at'),
                        false: fn (Builder $q) => $q->whereNotNull('cerrada_at'),
                    ),
                SelectFilter::make('etapa_id')->label('Etapa')->relationship('etapa', 'nombre', fn ($query) => $query->orderBy('orden'))->multiple(),
                SelectFilter::make('tipo')->label('Tipo')->options(TipoOrden::class),
                SelectFilter::make('aseguradora_id')->label('Aseguradora')->relationship('aseguradora', 'nombre'),
                SelectFilter::make('asesor_id')->label('Asesor')->relationship('asesor', 'name'),
            ])
            ->defaultSort('fecha_ingreso', 'desc')
            ->recordUrl(fn (OrdenServicio $record) => OrdenServicioResource::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()]);
    }

    /**
     * Versión compacta para el historial dentro de cliente o vehículo.
     */
    public static function historial(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehiculo', 'etapa', 'checkin']))
            ->columns(self::columnas(compacta: true))
            ->defaultSort('fecha_ingreso', 'desc')
            ->recordUrl(fn (OrdenServicio $record) => OrdenServicioResource::getUrl('view', ['record' => $record]));
    }

    private static function columnas(bool $compacta = false): array
    {
        return array_values(array_filter([
            TextColumn::make('folio')->label('Folio')->searchable()->weight('bold')->copyable(),
            TextColumn::make('fecha_ingreso')->label('Ingreso')->date('d/m/Y')->sortable(),
            TextColumn::make('vehiculo.placas')->label('Vehículo')
                ->formatStateUsing(fn (OrdenServicio $record) => $record->vehiculo?->descripcion)
                ->searchable(['placas', 'vin']),
            $compacta ? null : TextColumn::make('cliente.nombre')->label('Cliente')
                ->formatStateUsing(fn (OrdenServicio $record) => $record->cliente?->nombre_completo)
                ->searchable(['nombre', 'apellidos']),
            TextColumn::make('tipo')->label('Tipo')->badge(),
            TextColumn::make('etapa.nombre')->label('Etapa')->badge()
                ->color(fn (OrdenServicio $record) => $record->etapa?->color ?? 'gray'),
            $compacta ? null : TextColumn::make('etapa_desde')->label('Días en etapa')
                ->state(fn (OrdenServicio $record) => $record->cerrada_at ? null : $record->diasEnEtapa())
                ->badge()
                ->color(fn (OrdenServicio $record) => self::colorDias($record))
                ->placeholder('—')
                ->sortable(),
            IconColumn::make('checkin_estado')->label('Check-in')
                ->state(fn (OrdenServicio $record) => match (true) {
                    $record->checkin?->estaFirmado() => 'firmado',
                    $record->checkin !== null => 'pendiente',
                    default => 'sin',
                })
                ->icon(fn (string $state) => match ($state) {
                    'firmado' => 'heroicon-o-check-badge',
                    'pendiente' => 'heroicon-o-pencil-square',
                    default => 'heroicon-o-minus-circle',
                })
                ->color(fn (string $state) => match ($state) {
                    'firmado' => 'success',
                    'pendiente' => 'warning',
                    default => 'gray',
                })
                ->tooltip(fn (string $state) => match ($state) {
                    'firmado' => 'Firmado por el cliente',
                    'pendiente' => 'Falta la firma del cliente',
                    default => 'Sin check-in',
                }),
            $compacta ? null : TextColumn::make('fecha_promesa')->label('Promesa')->dateTime('d/m H:i')->placeholder('—')->sortable()->toggleable(),
            $compacta ? null : TextColumn::make('asesor.name')->label('Asesor')->toggleable(isToggledHiddenByDefault: true),
        ]));
    }

    private static function colorDias(OrdenServicio $record): string
    {
        $max = $record->etapa?->dias_max;

        if (! $max) {
            return 'gray';
        }

        $dias = $record->diasEnEtapa();

        return match (true) {
            $dias > $max => 'danger',
            $dias >= $max - 1 => 'warning',
            default => 'success',
        };
    }
}
