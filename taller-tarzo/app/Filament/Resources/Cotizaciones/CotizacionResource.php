<?php

namespace App\Filament\Resources\Cotizaciones;

use App\Enums\EstadoCotizacion;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Cotizaciones\Pages\EditCotizacion;
use App\Filament\Resources\Cotizaciones\Pages\ListCotizaciones;
use App\Filament\Resources\Cotizaciones\Schemas\CotizacionForm;
use App\Filament\Resources\Cotizaciones\Tables\CotizacionesTable;
use App\Models\Cotizacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CotizacionResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'cotizacion';

    protected static ?string $model = Cotizacion::class;

    protected static ?string $slug = 'cotizaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Cotización';

    protected static ?int $navigationSort = 1;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'cotización';

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    protected static ?string $recordTitleAttribute = 'folio';

    public static function canCreate(): bool
    {
        return false; // se crean desde la orden de servicio
    }

    /** Se puede abrir siempre que se tenga permiso de consulta; el formulario se bloquea si no es borrador. */
    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('cotizacion.ver');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CotizacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CotizacionesTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['folio'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('orden.vehiculo');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Vehículo' => $record->orden?->vehiculo?->descripcion, 'Estado' => $record->estado->getLabel()];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Cotizacion::where('estado', EstadoCotizacion::Enviada->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Esperando autorización';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCotizaciones::route('/'),
            'edit' => EditCotizacion::route('/{record}'),
        ];
    }
}
