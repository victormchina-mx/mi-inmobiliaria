<?php

namespace App\Filament\Resources\OrdenesCompra;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\OrdenesCompra\Pages\CreateOrdenCompra;
use App\Filament\Resources\OrdenesCompra\Pages\EditOrdenCompra;
use App\Filament\Resources\OrdenesCompra\Pages\ListOrdenesCompra;
use App\Filament\Resources\OrdenesCompra\Schemas\OrdenCompraForm;
use App\Filament\Resources\OrdenesCompra\Tables\OrdenesCompraTable;
use App\Models\OrdenCompra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrdenCompraResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'almacen';

    protected static ?string $model = OrdenCompra::class;

    protected static ?string $slug = 'ordenes-compra';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Almacén';

    protected static ?int $navigationSort = 3;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'orden de compra';

    protected static ?string $pluralModelLabel = 'Órdenes de compra';

    protected static ?string $recordTitleAttribute = 'folio';

    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('almacen.ver');
    }

    public static function form(Schema $schema): Schema
    {
        return OrdenCompraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdenesCompraTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = OrdenCompra::whereNotIn('estado', ['borrador', 'recibida', 'cancelada'])->whereDate('fecha_estimada', '<', today())->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Compras atrasadas';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdenesCompra::route('/'),
            'create' => CreateOrdenCompra::route('/create'),
            'edit' => EditOrdenCompra::route('/{record}'),
        ];
    }
}
