<?php

namespace App\Filament\Resources\OrdenesCompra\Tables;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdenesCompraTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('proveedor')->withCount('partidas'))
            ->columns([
                TextColumn::make('folio')->label('Folio')->searchable()->weight('bold'),
                TextColumn::make('proveedor.nombre')->label('Proveedor')->searchable(),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('partidas_count')->label('Partidas'),
                TextColumn::make('subtotal')->label('Subtotal')->money('MXN'),
                TextColumn::make('fecha_estimada')->label('Llega')->date('d/m/Y')->sortable()
                    ->color(fn (OrdenCompra $r) => $r->atrasada() ? 'danger' : null)
                    ->description(fn (OrdenCompra $r) => $r->atrasada() ? 'Atrasada' : null),
                TextColumn::make('created_at')->label('Creada')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoOrdenCompra::class)->multiple(),
                Filter::make('abiertas')->label('Abiertas')->default()
                    ->query(fn (Builder $query) => $query->whereNotIn('estado', ['recibida', 'cancelada'])),
                Filter::make('atrasadas')->label('Atrasadas')
                    ->query(fn (Builder $query) => $query->whereNotIn('estado', ['borrador', 'recibida', 'cancelada'])->whereDate('fecha_estimada', '<', today())),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()->label('Abrir')]);
    }
}
