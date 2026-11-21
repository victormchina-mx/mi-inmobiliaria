<?php

namespace App\Filament\Resources\Flotillas;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Flotillas\Pages\CreateFlotilla;
use App\Filament\Resources\Flotillas\Pages\EditFlotilla;
use App\Filament\Resources\Flotillas\Pages\ListFlotillas;
use App\Filament\Resources\Flotillas\Pages\ViewFlotilla;
use App\Filament\Resources\Flotillas\RelationManagers\ClientesRelationManager;
use App\Filament\Resources\Flotillas\RelationManagers\ContactosRelationManager;
use App\Filament\Resources\Flotillas\Schemas\FlotillaForm;
use App\Filament\Resources\Flotillas\Schemas\FlotillaInfolist;
use App\Filament\Resources\Flotillas\Tables\FlotillasTable;
use App\Models\Flotilla;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FlotillaResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'recepcion';

    protected static ?string $model = Flotilla::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Clientes';

    protected static ?int $navigationSort = 3;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'flotilla';

    protected static ?string $pluralModelLabel = 'Flotillas';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return FlotillaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FlotillaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FlotillasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ClientesRelationManager::class, ContactosRelationManager::class];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlotillas::route('/'),
            'create' => CreateFlotilla::route('/create'),
            'view' => ViewFlotilla::route('/{record}'),
            'edit' => EditFlotilla::route('/{record}/edit'),
        ];
    }
}
