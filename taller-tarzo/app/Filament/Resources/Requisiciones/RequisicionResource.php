<?php

namespace App\Filament\Resources\Requisiciones;

use App\Enums\EstadoRequisicion;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Requisiciones\Pages\ListRequisiciones;
use App\Filament\Resources\Requisiciones\Tables\RequisicionesTable;
use App\Models\Requisicion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Piezas que las órdenes necesitan y hay que conseguir.
 */
class RequisicionResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'almacen';

    protected static ?string $model = Requisicion::class;

    protected static ?string $slug = 'piezas-por-pedir';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Almacén';

    protected static ?int $navigationSort = 2;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'pieza por surtir';

    protected static ?string $pluralModelLabel = 'Piezas por pedir';

    public static function canCreate(): bool
    {
        return false; // nacen de las partidas autorizadas
    }

    public static function table(Table $table): Table
    {
        return RequisicionesTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Requisicion::where('estado', EstadoRequisicion::Pendiente->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return ['index' => ListRequisiciones::route('/')];
    }
}
