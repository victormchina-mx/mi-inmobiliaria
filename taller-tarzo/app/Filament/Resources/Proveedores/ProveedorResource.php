<?php

namespace App\Filament\Resources\Proveedores;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Proveedores\Pages\CreateProveedor;
use App\Filament\Resources\Proveedores\Pages\EditProveedor;
use App\Filament\Resources\Proveedores\Pages\ListProveedores;
use App\Filament\Resources\Proveedores\Schemas\ProveedorForm;
use App\Filament\Resources\Proveedores\Tables\ProveedoresTable;
use App\Models\Proveedor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProveedorResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'almacen';

    protected static ?string $model = Proveedor::class;

    protected static ?string $slug = 'proveedores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Almacén';

    protected static ?int $navigationSort = 5;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return ProveedorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProveedoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProveedores::route('/'),
            'create' => CreateProveedor::route('/create'),
            'edit' => EditProveedor::route('/{record}/edit'),
        ];
    }
}
