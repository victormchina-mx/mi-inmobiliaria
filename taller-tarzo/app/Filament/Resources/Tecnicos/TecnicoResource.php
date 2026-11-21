<?php

namespace App\Filament\Resources\Tecnicos;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Tecnicos\Pages\CreateTecnico;
use App\Filament\Resources\Tecnicos\Pages\EditTecnico;
use App\Filament\Resources\Tecnicos\Pages\ListTecnicos;
use App\Filament\Resources\Tecnicos\Schemas\TecnicoForm;
use App\Filament\Resources\Tecnicos\Tables\TecnicosTable;
use App\Models\Tecnico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TecnicoResource extends Resource
{
    protected static ?string $model = Tecnico::class;

    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 3;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'técnico';

    protected static ?string $pluralModelLabel = 'Técnicos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return TecnicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TecnicosTable::configure($table);
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
            'index' => ListTecnicos::route('/'),
            'create' => CreateTecnico::route('/create'),
            'edit' => EditTecnico::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
