<?php

namespace App\Filament\Resources\Parametros;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Parametros\Pages\ListParametros;
use App\Filament\Resources\Parametros\Schemas\ParametroForm;
use App\Filament\Resources\Parametros\Tables\ParametrosTable;
use App\Models\Parametro;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ParametroResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static ?string $model = Parametro::class;

    protected static ?string $slug = 'parametros';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 13;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'parámetro';

    protected static ?string $pluralModelLabel = 'Parámetros de cotización';

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function form(Schema $schema): Schema
    {
        return ParametroForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParametrosTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParametros::route('/'),
        ];
    }
}
