<?php

namespace App\Filament\Resources\Productos\Schemas;

use App\Enums\CategoriaProducto;
use App\Enums\UnidadMedida;
use App\Models\Producto;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Producto')->columns(3)->schema([
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(200)->columnSpan(2),
                TextInput::make('sku')->label('Clave (SKU)')->required()->maxLength(40)->unique(ignoreRecord: true),
                Select::make('categoria')->label('Categoría')->options(CategoriaProducto::class)->required(),
                Select::make('unidad')->label('Unidad')->options(UnidadMedida::class)->required()->default(UnidadMedida::Pieza),
                TextInput::make('codigo_barras')->label('Código de barras')->maxLength(60)->unique(ignoreRecord: true),
                TextInput::make('ubicacion')->label('Ubicación')->placeholder('Anaquel A-3')->maxLength(60),
                Select::make('proveedor_id')->label('Proveedor habitual')->relationship('proveedor', 'nombre')->searchable()->preload(),
                Toggle::make('activo')->label('Activo')->default(true)->inline(false),
            ]),
            Section::make('Reposición')->columns(3)->schema([
                TextInput::make('stock_minimo')->label('Stock mínimo')->numeric()->minValue(0)->default(0)
                    ->helperText('Al bajar de aquí se avisa a almacén.'),
                TextInput::make('stock_maximo')->label('Stock máximo')->numeric()->minValue(0)->default(0)
                    ->helperText('Hasta dónde se sugiere reponer.'),
                Text::make(fn (?Producto $record) => $record
                    ? "Existencia: {$record->existencia} {$record->unidad->getLabel()} · Costo promedio: $".number_format($record->costo_promedio, 2).' · Valor: $'.number_format($record->valorInventario(), 2)
                    : 'La existencia inicial se captura con una "Entrada" después de guardar.'),
            ]),
        ])->disabled(fn () => ! auth()->user()?->can('almacen.gestionar'));
    }
}
