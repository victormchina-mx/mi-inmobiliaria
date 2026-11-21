<?php

namespace App\Filament\Resources\PiezaCarrocerias;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\PiezaCarrocerias\Pages\CreatePiezaCarroceria;
use App\Filament\Resources\PiezaCarrocerias\Pages\EditPiezaCarroceria;
use App\Filament\Resources\PiezaCarrocerias\Pages\ListPiezaCarrocerias;
use App\Filament\Resources\PiezaCarrocerias\Schemas\PiezaCarroceriaForm;
use App\Filament\Resources\PiezaCarrocerias\Tables\PiezaCarroceriasTable;
use App\Models\PiezaCarroceria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PiezaCarroceriaResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'catalogos';

    protected static ?string $model = PiezaCarroceria::class;

    protected static ?string $slug = 'piezas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 11;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'pieza';

    protected static ?string $pluralModelLabel = 'Piezas de carrocería';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PiezaCarroceriaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PiezaCarroceriasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPiezaCarrocerias::route('/'),
            'create' => CreatePiezaCarroceria::route('/create'),
            'edit' => EditPiezaCarroceria::route('/{record}/edit'),
        ];
    }
}
