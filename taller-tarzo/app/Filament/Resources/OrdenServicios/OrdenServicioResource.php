<?php

namespace App\Filament\Resources\OrdenServicios;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\OrdenServicios\Pages\CreateOrdenServicio;
use App\Filament\Resources\OrdenServicios\Pages\EditOrdenServicio;
use App\Filament\Resources\OrdenServicios\Pages\ListOrdenServicios;
use App\Filament\Resources\OrdenServicios\Pages\ViewOrdenServicio;
use App\Filament\Resources\OrdenServicios\RelationManagers\ConsumosRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\CotizacionesRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\DiagnosticosRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\EvidenciasRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\MensajesRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\RefaccionesRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\TareasRelationManager;
use App\Filament\Resources\OrdenServicios\Schemas\OrdenServicioForm;
use App\Filament\Resources\OrdenServicios\Schemas\OrdenServicioInfolist;
use App\Filament\Resources\OrdenServicios\Tables\OrdenServiciosTable;
use App\Models\OrdenServicio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OrdenServicioResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'recepcion';

    protected static ?string $model = OrdenServicio::class;

    protected static ?string $slug = 'ordenes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Recepción';

    protected static ?int $navigationSort = 1;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'orden de servicio';

    protected static ?string $pluralModelLabel = 'Órdenes de servicio';

    protected static ?string $recordTitleAttribute = 'folio';

    public static function form(Schema $schema): Schema
    {
        return OrdenServicioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrdenServicioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdenServiciosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [DiagnosticosRelationManager::class, CotizacionesRelationManager::class, TareasRelationManager::class, RefaccionesRelationManager::class, EvidenciasRelationManager::class, ConsumosRelationManager::class, MensajesRelationManager::class];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['folio', 'numero_siniestro', 'vehiculo.placas', 'vehiculo.vin'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Vehículo' => $record->vehiculo?->descripcion,
            'Etapa' => $record->etapa?->nombre,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['vehiculo', 'etapa']);
    }

    public static function getNavigationBadge(): ?string
    {
        $abiertas = OrdenServicio::whereNull('cerrada_at')->count();

        return $abiertas > 0 ? (string) $abiertas : null;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdenServicios::route('/'),
            'create' => CreateOrdenServicio::route('/recibir'),
            'view' => ViewOrdenServicio::route('/{record}'),
            'edit' => EditOrdenServicio::route('/{record}/edit'),
        ];
    }
}
