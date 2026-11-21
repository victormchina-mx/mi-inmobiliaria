<?php

namespace App\Filament\Resources\Bahias;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Bahias\Pages\CreateBahia;
use App\Filament\Resources\Bahias\Pages\EditBahia;
use App\Filament\Resources\Bahias\Pages\ListBahias;
use App\Filament\Resources\Bahias\Schemas\BahiaForm;
use App\Filament\Resources\Bahias\Tables\BahiasTable;
use App\Models\Bahia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BahiaResource extends Resource
{
    protected static ?string $model = Bahia::class;

    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 2;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'bahía';

    protected static ?string $pluralModelLabel = 'Bahías y cabinas';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return BahiaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BahiasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBahias::route('/'),
            'create' => CreateBahia::route('/create'),
            'edit' => EditBahia::route('/{record}/edit'),
        ];
    }
}
