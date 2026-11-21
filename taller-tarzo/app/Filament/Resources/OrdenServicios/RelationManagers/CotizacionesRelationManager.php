<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Filament\Resources\Cotizaciones\Tables\CotizacionesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Versiones del presupuesto de la orden (C1, C2…).
 */
class CotizacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'cotizaciones';

    protected static ?string $title = 'Cotizaciones';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('cotizacion.ver');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return CotizacionesTable::columnas($table, compacta: true)->defaultSort('version', 'desc')->paginated(false);
    }
}
