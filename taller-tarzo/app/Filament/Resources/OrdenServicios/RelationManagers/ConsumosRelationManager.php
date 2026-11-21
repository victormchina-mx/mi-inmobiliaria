<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\Inventario\Inventario;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Material de almacén cargado a la orden (aceite, lijas, pintura…): su costo entra en la utilidad de la orden.
 */
class ConsumosRelationManager extends RelationManager
{
    protected static string $relationship = 'consumos';

    protected static ?string $title = 'Consumos de almacén';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('almacen.ver');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('producto'))
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m H:i'),
                TextColumn::make('producto.nombre')->label('Producto'),
                TextColumn::make('tipo')->label('Movimiento')->badge(),
                TextColumn::make('cantidad')->label('Cantidad')->formatStateUsing(fn (MovimientoInventario $r) => abs($r->cantidad).' '.$r->producto->unidad->getLabel()),
                TextColumn::make('costo_total')->label('Costo')->money('MXN')
                    ->formatStateUsing(fn (MovimientoInventario $r) => ($r->cantidad > 0 ? '−' : '').'$'.number_format($r->costo_total, 2)),
            ])
            ->headerActions([
                Action::make('salida')->label('Cargar material')->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn () => auth()->user()?->can('almacen.gestionar') && $this->getOwnerRecord()->cerrada_at === null)
                    ->schema(fn (Schema $schema) => $schema->columns(2)->components([
                        Select::make('producto_id')->label('Producto')->required()->searchable()
                            ->options(fn () => Producto::where('activo', true)->orderBy('nombre')->get()
                                ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} ({$p->sku}) · {$p->existencia} {$p->unidad->getLabel()}"])),
                        TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.001)->required(),
                    ]))
                    ->action(function (array $data) {
                        try {
                            app(Inventario::class)->salida(Producto::findOrFail($data['producto_id']), (float) $data['cantidad'], $this->getOwnerRecord(), auth()->user());
                            Notification::make()->title('Material cargado a la orden')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Sin consumos')
            ->emptyStateDescription('Carga aquí el material de almacén que usó esta orden.');
    }
}
