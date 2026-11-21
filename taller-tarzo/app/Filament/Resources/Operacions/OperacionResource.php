<?php

namespace App\Filament\Resources\Operacions;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Operacions\Pages\CreateOperacion;
use App\Filament\Resources\Operacions\Pages\EditOperacion;
use App\Filament\Resources\Operacions\Pages\ListOperacions;
use App\Filament\Resources\Operacions\Schemas\OperacionForm;
use App\Filament\Resources\Operacions\Tables\OperacionsTable;
use App\Models\Operacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OperacionResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static ?string $model = Operacion::class;

    protected static ?string $slug = 'operaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 10;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'operación';

    protected static ?string $pluralModelLabel = 'Operaciones de mano de obra';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return OperacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OperacionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOperacions::route('/'),
            'create' => CreateOperacion::route('/create'),
            'edit' => EditOperacion::route('/{record}/edit'),
        ];
    }
}
