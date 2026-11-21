<?php

namespace App\Filament\Resources\OrdenesCompra\Schemas;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Producto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrdenCompraForm
{
    public static function configure(Schema $schema): Schema
    {
        $editable = fn (?OrdenCompra $record) => auth()->user()?->can('almacen.gestionar') && ($record === null || $record->estado === EstadoOrdenCompra::Borrador);

        return $schema->columns(1)->components([
            Section::make('Orden de compra')->columns(3)->schema([
                Select::make('proveedor_id')->label('Proveedor')->relationship('proveedor', 'nombre', fn ($query) => $query->where('activo', true))
                    ->preload()->searchable()->required()->disabled(fn (?OrdenCompra $record) => ! $editable($record)),
                DatePicker::make('fecha_estimada')->label('Llega el')->native(false)->displayFormat('d/m/Y')
                    ->disabled(fn () => ! auth()->user()?->can('almacen.gestionar')),
                Text::make(fn (?OrdenCompra $record) => $record ? 'Estado: '.$record->estado->getLabel().($record->atrasada() ? ' · ATRASADA' : '') : '')
                    ->color(fn (?OrdenCompra $record) => $record?->atrasada() ? 'danger' : null),
                Textarea::make('notas')->label('Notas para el proveedor')->rows(2)->columnSpanFull()
                    ->disabled(fn () => ! auth()->user()?->can('almacen.gestionar')),
            ]),
            Section::make('Partidas')->schema([
                Repeater::make('partidas')->hiddenLabel()->relationship()
                    ->schema([
                        Select::make('producto_id')->label('Producto de almacén')
                            ->options(fn () => Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()->live()
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                if ($p = Producto::find($state)) {
                                    $set('descripcion', $p->nombre);
                                    $set('numero_parte', $p->sku);
                                    $set('costo_unitario', $p->costo_promedio);
                                    $set('cantidad', $p->sugerenciaCompra() ?: 1);
                                }
                            })
                            ->helperText('Vacío si es una pieza para una orden.')
                            ->columnSpan(2),
                        TextInput::make('descripcion')->label('Descripción')->required()->maxLength(255)->columnSpan(3),
                        TextInput::make('numero_parte')->label('No. parte')->maxLength(60),
                        TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.001)->required()->default(1)->live(onBlur: true),
                        TextInput::make('costo_unitario')->label('Costo estimado')->numeric()->minValue(0)->prefix('$')->default(0)->live(onBlur: true),
                        Text::make(fn (Get $get) => 'Recibido: '.rtrim(rtrim(number_format((float) $get('cantidad_recibida'), 3), '0'), '.') ?: '0')
                            ->visible(fn ($livewire) => ($oc = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null) instanceof OrdenCompra
                                && $oc->estado !== EstadoOrdenCompra::Borrador),
                    ])
                    ->columns(6)
                    ->addActionLabel('Agregar partida')
                    ->defaultItems(0)
                    ->live()
                    ->disabled(fn (?OrdenCompra $record) => ! $editable($record))
                    ->deletable(fn (?OrdenCompra $record) => $editable($record))
                    ->addable(fn (?OrdenCompra $record) => $editable($record)),
                Text::make(fn (Get $get) => 'Subtotal estimado: $'.number_format(collect($get('partidas') ?? [])->sum(fn ($p) => (float) ($p['cantidad'] ?? 0) * (float) ($p['costo_unitario'] ?? 0)), 2))
                    ->weight('bold'),
            ]),
        ]);
    }
}
