<?php

namespace App\Filament\Resources\Productos;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Productos\Pages\CreateProducto;
use App\Filament\Resources\Productos\Pages\EditProducto;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\RelationManagers\MovimientosRelationManager;
use App\Filament\Resources\Productos\Schemas\ProductoForm;
use App\Filament\Resources\Productos\Tables\ProductosTable;
use App\Models\Producto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductoResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'almacen';

    protected static ?string $model = Producto::class;

    protected static ?string $slug = 'productos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Almacén';

    protected static ?int $navigationSort = 1;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'Inventario';

    protected static ?string $recordTitleAttribute = 'nombre';

    /** Se abre la ficha aunque solo se tenga permiso de consulta (los campos se bloquean). */
    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('almacen.ver');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [MovimientosRelationManager::class];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['sku', 'codigo_barras', 'nombre'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Existencia' => $record->existencia.' '.$record->unidad->getLabel(), 'Ubicación' => $record->ubicacion];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Producto::where('activo', true)->bajoMinimo()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Productos bajo el mínimo';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductos::route('/'),
            'create' => CreateProducto::route('/create'),
            'edit' => EditProducto::route('/{record}/edit'),
        ];
    }
}
