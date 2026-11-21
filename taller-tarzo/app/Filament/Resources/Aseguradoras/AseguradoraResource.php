<?php

namespace App\Filament\Resources\Aseguradoras;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Aseguradoras\Pages\CreateAseguradora;
use App\Filament\Resources\Aseguradoras\Pages\EditAseguradora;
use App\Filament\Resources\Aseguradoras\Pages\ListAseguradoras;
use App\Filament\Resources\Aseguradoras\Schemas\AseguradoraForm;
use App\Filament\Resources\Aseguradoras\Tables\AseguradorasTable;
use App\Models\Aseguradora;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AseguradoraResource extends Resource
{
    protected static ?string $model = Aseguradora::class;

    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 5;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'aseguradora';

    protected static ?string $pluralModelLabel = 'Aseguradoras';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return AseguradoraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AseguradorasTable::configure($table);
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
            'index' => ListAseguradoras::route('/'),
            'create' => CreateAseguradora::route('/create'),
            'edit' => EditAseguradora::route('/{record}/edit'),
        ];
    }
}
