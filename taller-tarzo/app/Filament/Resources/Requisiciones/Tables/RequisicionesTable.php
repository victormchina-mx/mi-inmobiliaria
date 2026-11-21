<?php

namespace App\Filament\Resources\Requisiciones\Tables;

use App\Actions\Almacen\CrearOrdenCompra;
use App\Actions\Almacen\RecibirMercancia;
use App\Actions\Almacen\VerificarRefacciones;
use App\Enums\EstadoRequisicion;
use App\Filament\Resources\OrdenesCompra\OrdenCompraResource;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Proveedor;
use App\Models\Requisicion;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RequisicionesTable
{
    public static function configure(Table $table, bool $deUnaOrden = false): Table
    {
        $puede = fn () => auth()->user()?->can('almacen.gestionar');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['orden.vehiculo', 'partidaCompra.ordenCompra']))
            ->columns(array_values(array_filter([
                $deUnaOrden ? null : TextColumn::make('orden.folio')->label('Orden')->searchable()->weight('bold')
                    ->url(fn (Requisicion $r) => OrdenServicioResource::getUrl('view', ['record' => $r->orden_servicio_id]))
                    ->description(fn (Requisicion $r) => $r->orden->vehiculo->descripcion),
                TextColumn::make('descripcion')->label('Pieza')->searchable()->wrap()
                    ->description(fn (Requisicion $r) => $r->numero_parte ? 'No. parte '.$r->numero_parte : null),
                TextColumn::make('cantidad')->label('Cant.')->numeric(2),
                TextColumn::make('surte')->label('Surte')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'aseguradora' ? 'Aseguradora' : 'Taller')
                    ->color(fn (string $state) => $state === 'aseguradora' ? 'info' : 'gray'),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('partidaCompra.ordenCompra.folio')->label('Orden de compra')->placeholder('—')
                    ->url(fn (Requisicion $r) => $r->partidaCompra ? OrdenCompraResource::getUrl('edit', ['record' => $r->partidaCompra->orden_compra_id]) : null)
                    ->description(fn (Requisicion $r) => $r->partidaCompra?->ordenCompra?->fecha_estimada ? 'Llega '.$r->partidaCompra->ordenCompra->fecha_estimada->format('d/m') : null),
                TextColumn::make('costo_real')->label('Costo real')->money('MXN')->placeholder('—'),
                TextColumn::make('created_at')->label('Desde')->since()->sortable(),
            ])))
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoRequisicion::class)->multiple()
                    ->default($deUnaOrden ? [] : [EstadoRequisicion::Pendiente->value, EstadoRequisicion::Ordenada->value]),
                SelectFilter::make('surte')->label('Surte')->options(['taller' => 'Taller', 'aseguradora' => 'Aseguradora']),
            ])
            ->defaultSort('created_at')
            ->recordActions([
                ActionGroup::make([
                    Action::make('recibidaAseguradora')->label('Recibida de la aseguradora')->icon('heroicon-o-check')
                        ->visible(fn (Requisicion $r) => $r->surte === 'aseguradora' && $r->estado !== EstadoRequisicion::Recibida)
                        ->requiresConfirmation()
                        ->action(function (Requisicion $r) {
                            app(RecibirMercancia::class)->deAseguradora($r);
                            Notification::make()->title('Pieza recibida')->success()->send();
                        }),
                    Action::make('surteAseguradora')->label('La surte la aseguradora')->icon('heroicon-o-shield-check')
                        ->visible(fn (Requisicion $r) => $r->surte === 'taller' && $r->estado === EstadoRequisicion::Pendiente)
                        ->action(fn (Requisicion $r) => $r->update(['surte' => 'aseguradora'])),
                    Action::make('surteTaller')->label('La compra el taller')->icon('heroicon-o-building-storefront')
                        ->visible(fn (Requisicion $r) => $r->surte === 'aseguradora' && $r->estado === EstadoRequisicion::Pendiente)
                        ->action(fn (Requisicion $r) => $r->update(['surte' => 'taller'])),
                    Action::make('cancelar')->label('Ya no se necesita')->icon('heroicon-o-x-mark')->color('danger')
                        ->visible(fn (Requisicion $r) => $r->estado === EstadoRequisicion::Pendiente)
                        ->requiresConfirmation()
                        ->action(function (Requisicion $r) {
                            $r->update(['estado' => EstadoRequisicion::Cancelada]);
                            app(VerificarRefacciones::class)($r->orden);
                        }),
                ])->visible($puede),
            ])
            ->toolbarActions([
                BulkAction::make('ordenCompra')->label('Crear orden de compra')->icon('heroicon-o-shopping-cart')
                    ->visible($puede)
                    ->schema(fn (Schema $schema) => $schema->components([
                        Select::make('proveedor_id')->label('Proveedor')->options(Proveedor::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))->required(),
                    ]))
                    ->action(function (Collection $records, array $data, $livewire) {
                        try {
                            $oc = app(CrearOrdenCompra::class)->desdeRequisiciones($records, Proveedor::findOrFail($data['proveedor_id']), auth()->user());
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->warning()->send();

                            return;
                        }
                        Notification::make()->title("Orden de compra {$oc->folio} creada")->body('Revisa costos y envíala al proveedor.')->success()->send();
                        $livewire->redirect(OrdenCompraResource::getUrl('edit', ['record' => $oc]));
                    }),
            ])
            ->emptyStateHeading('No hay piezas por pedir')
            ->emptyStateDescription('Aparecen solas cuando se autoriza una refacción que no está en almacén.');
    }
}
