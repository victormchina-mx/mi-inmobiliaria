<?php

namespace App\Filament\Actions;

use App\Models\OrdenServicio;
use App\Models\Producto;
use App\Services\Inventario\Inventario;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Acciones de movimiento de inventario (sirven en la tabla y en la ficha del producto).
 */
final class AccionesInventario
{
    private static function puede(): bool
    {
        return (bool) auth()->user()?->can('almacen.gestionar');
    }

    private static function ejecutar(callable $fn, string $ok): void
    {
        try {
            $fn();
            Notification::make()->title($ok)->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title('No se registró')->body(collect($e->errors())->flatten()->first())->danger()->send();
        }
    }

    public static function selectOrden(): Select
    {
        return Select::make('orden_servicio_id')->label('Orden de servicio')
            ->searchable()
            ->options(fn () => OrdenServicio::with('vehiculo')->whereNull('cerrada_at')->latest('id')->limit(300)->get()
                ->mapWithKeys(fn ($o) => [$o->id => "{$o->folio} · {$o->vehiculo->descripcion}"]));
    }

    public static function entrada(): Action
    {
        return Action::make('entrada')->label('Entrada')->icon(Heroicon::OutlinedArrowDownTray)->color('success')
            ->visible(fn () => self::puede())
            ->modalDescription('Compra directa sin orden de compra (las compras con orden se reciben desde la orden de compra).')
            ->schema(fn (Schema $schema, Producto $record) => $schema->columns(2)->components([
                TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.001)->required()->suffix($record->unidad->getLabel()),
                TextInput::make('costo')->label('Costo unitario')->numeric()->minValue(0)->prefix('$')->required()->default($record->costo_promedio),
                TextInput::make('referencia')->label('Referencia (factura, nota)')->maxLength(255)->columnSpanFull(),
            ]))
            ->action(fn (array $data, Producto $record) => self::ejecutar(
                fn () => app(Inventario::class)->entrada($record, (float) $data['cantidad'], (float) $data['costo'], auth()->user(), $data['referencia'] ?? null),
                'Entrada registrada'
            ));
    }

    public static function salida(): Action
    {
        return Action::make('salida')->label('Salida')->icon(Heroicon::OutlinedArrowUpTray)->color('warning')
            ->visible(fn () => self::puede())
            ->schema(fn (Schema $schema, Producto $record) => $schema->columns(2)->components([
                TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.001)->required()
                    ->suffix($record->unidad->getLabel())->helperText("Existencia: {$record->existencia}"),
                Toggle::make('gasto_general')->label('Gasto general (no es para una orden)')->live()->inline(false),
                self::selectOrden()->required(fn (Get $get) => ! $get('gasto_general'))->visible(fn (Get $get) => ! $get('gasto_general'))->columnSpanFull(),
                TextInput::make('motivo')->label('Motivo del gasto general')->maxLength(255)
                    ->required(fn (Get $get) => (bool) $get('gasto_general'))->visible(fn (Get $get) => (bool) $get('gasto_general'))->columnSpanFull(),
            ]))
            ->action(fn (array $data, Producto $record) => self::ejecutar(
                fn () => app(Inventario::class)->salida($record, (float) $data['cantidad'],
                    ($data['gasto_general'] ?? false) ? null : OrdenServicio::find($data['orden_servicio_id'] ?? null),
                    auth()->user(), ($data['gasto_general'] ?? false) ? $data['motivo'] : null),
                'Salida registrada'
            ));
    }

    public static function devolucion(): Action
    {
        return Action::make('devolucion')->label('Devolución')->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
            ->visible(fn () => self::puede())
            ->modalDescription('Material que regresa al almacén desde una orden.')
            ->schema(fn (Schema $schema, Producto $record) => $schema->components([
                self::selectOrden()->required(),
                TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.001)->required()->suffix($record->unidad->getLabel()),
            ]))
            ->action(fn (array $data, Producto $record) => self::ejecutar(
                fn () => app(Inventario::class)->devolucion($record, (float) $data['cantidad'], OrdenServicio::findOrFail($data['orden_servicio_id']), auth()->user()),
                'Devolución registrada'
            ));
    }

    public static function ajuste(): Action
    {
        return Action::make('ajuste')->label('Ajustar')->icon(Heroicon::OutlinedScale)->color('gray')
            ->visible(fn () => self::puede())
            ->schema(fn (Schema $schema, Producto $record) => $schema->components([
                TextInput::make('contado')->label('Existencia real contada')->numeric()->minValue(0)->required()
                    ->suffix($record->unidad->getLabel())->default($record->existencia)->helperText("En sistema: {$record->existencia}"),
                TextInput::make('motivo')->label('Motivo')->required()->maxLength(255)->default('Conteo físico'),
            ]))
            ->action(fn (array $data, Producto $record) => self::ejecutar(
                fn () => app(Inventario::class)->ajustarA($record, (float) $data['contado'], auth()->user(), $data['motivo']),
                'Ajuste registrado'
            ));
    }
}
