<?php

namespace App\Filament\Resources\Productos\RelationManagers;

use App\Enums\TipoMovimiento;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\MovimientoInventario;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Kárdex del producto.
 */
class MovimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientos';

    protected static ?string $title = 'Kárdex';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('almacen.ver');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['orden', 'usuario']))
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('tipo')->label('Movimiento')->badge(),
                TextColumn::make('cantidad')->label('Cantidad')->numeric(3)
                    ->color(fn (MovimientoInventario $r) => $r->cantidad < 0 ? 'danger' : 'success'),
                TextColumn::make('costo_unitario')->label('Costo unit.')->money('MXN'),
                TextColumn::make('costo_total')->label('Importe')->money('MXN'),
                TextColumn::make('existencia_resultante')->label('Existencia')->numeric(3),
                TextColumn::make('costo_promedio_resultante')->label('Costo prom.')->money('MXN'),
                TextColumn::make('referencia')->label('Referencia')->placeholder('—')
                    ->url(fn (MovimientoInventario $r) => $r->orden_servicio_id ? OrdenServicioResource::getUrl('view', ['record' => $r->orden_servicio_id]) : null)
                    ->description(fn (MovimientoInventario $r) => $r->gasto_general ? 'Gasto general' : null),
                TextColumn::make('usuario.name')->label('Usuario')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([SelectFilter::make('tipo')->label('Tipo')->options(TipoMovimiento::class)])
            ->defaultSort('id', 'desc');
    }
}
