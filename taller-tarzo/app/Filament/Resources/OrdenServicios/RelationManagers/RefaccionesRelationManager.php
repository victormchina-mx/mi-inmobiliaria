<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Filament\Resources\Requisiciones\Tables\RequisicionesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Piezas que la orden necesita y en qué van (por pedir, pedidas, recibidas).
 */
class RefaccionesRelationManager extends RelationManager
{
    protected static string $relationship = 'requisiciones';

    protected static ?string $title = 'Refacciones';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $u = auth()->user();

        return (bool) ($u?->can('almacen.ver') || $u?->can('recepcion.ver'));
    }

    public function table(Table $table): Table
    {
        return RequisicionesTable::configure($table, deUnaOrden: true);
    }
}
