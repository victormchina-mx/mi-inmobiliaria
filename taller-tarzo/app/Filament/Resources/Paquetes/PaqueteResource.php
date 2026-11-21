<?php

namespace App\Filament\Resources\Paquetes;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Paquetes\Pages\CreatePaquete;
use App\Filament\Resources\Paquetes\Pages\EditPaquete;
use App\Filament\Resources\Paquetes\Pages\ListPaquetes;
use App\Filament\Resources\Paquetes\Schemas\PaqueteForm;
use App\Filament\Resources\Paquetes\Tables\PaquetesTable;
use App\Models\Paquete;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaqueteResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static ?string $model = Paquete::class;

    protected static ?string $slug = 'paquetes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 12;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'paquete';

    protected static ?string $pluralModelLabel = 'Paquetes de servicio';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PaqueteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaquetesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaquetes::route('/'),
            'create' => CreatePaquete::route('/create'),
            'edit' => EditPaquete::route('/{record}/edit'),
        ];
    }
}
