<?php

namespace App\Filament\Resources\Productos\Tables;

use App\Actions\Almacen\CrearOrdenCompra;
use App\Enums\CategoriaProducto;
use App\Filament\Actions\AccionesInventario;
use App\Filament\Resources\OrdenesCompra\OrdenCompraResource;
use App\Models\Producto;
use App\Models\Proveedor;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('proveedor'))
            ->columns([
                TextColumn::make('sku')->label('Clave')->searchable()->fontFamily('mono')->sortable(),
                TextColumn::make('nombre')->label('Producto')->searchable(['nombre', 'codigo_barras'])->sortable()->wrap()
                    ->description(fn (Producto $r) => $r->categoria->getLabel().($r->ubicacion ? ' · '.$r->ubicacion : '')),
                TextColumn::make('existencia')->label('Existencia')->sortable()->badge()
                    ->formatStateUsing(fn (Producto $r) => rtrim(rtrim(number_format($r->existencia, 3), '0'), '.').' '.$r->unidad->getLabel())
                    ->color(fn (Producto $r) => $r->bajoMinimo() ? 'danger' : ($r->existencia <= $r->stock_minimo * 1.25 && $r->stock_minimo > 0 ? 'warning' : 'success'))
                    ->description(fn (Producto $r) => $r->stock_minimo > 0 ? 'Mín. '.rtrim(rtrim(number_format($r->stock_minimo, 3), '0'), '.') : null),
                TextColumn::make('costo_promedio')->label('Costo prom.')->money('MXN')->sortable(),
                TextColumn::make('valor')->label('Valor')->money('MXN')
                    ->state(fn (Producto $r) => $r->valorInventario()),
                TextColumn::make('ultimo_movimiento_at')->label('Último movimiento')->since()->placeholder('Nunca')->sortable()->toggleable(),
                TextColumn::make('proveedor.nombre')->label('Proveedor')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('bajo_minimo')->label('Bajo el mínimo')->query(fn (Builder $query) => $query->bajoMinimo()),
                Filter::make('sin_movimiento')->label('Sin movimiento en 90 días')->query(fn (Builder $query) => $query->sinMovimiento(90)),
                SelectFilter::make('categoria')->label('Categoría')->options(CategoriaProducto::class),
                SelectFilter::make('proveedor_id')->label('Proveedor')->relationship('proveedor', 'nombre'),
            ])
            ->defaultSort('nombre')
            ->recordActions([
                ActionGroup::make([
                    AccionesInventario::entrada(),
                    AccionesInventario::salida(),
                    AccionesInventario::devolucion(),
                    AccionesInventario::ajuste(),
                ])->label('Movimiento')->button()->size('sm')->color('gray')
                    ->visible(fn () => auth()->user()?->can('almacen.gestionar')),
                EditAction::make()->label('Ver'),
            ])
            ->toolbarActions([
                BulkAction::make('reponer')->label('Orden de compra de reposición')->icon('heroicon-o-shopping-cart')
                    ->visible(fn () => auth()->user()?->can('almacen.gestionar'))
                    ->schema(fn (Schema $schema) => $schema->components([
                        Select::make('proveedor_id')->label('Proveedor')->options(Proveedor::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))->required(),
                    ]))
                    ->action(function (Collection $records, array $data, $livewire) {
                        try {
                            $oc = app(CrearOrdenCompra::class)->reposicion($records, Proveedor::findOrFail($data['proveedor_id']), auth()->user());
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->warning()->send();

                            return;
                        }
                        Notification::make()->title("Orden de compra {$oc->folio} creada")->success()->send();
                        $livewire->redirect(OrdenCompraResource::getUrl('edit', ['record' => $oc]));
                    }),
            ]);
    }
}
