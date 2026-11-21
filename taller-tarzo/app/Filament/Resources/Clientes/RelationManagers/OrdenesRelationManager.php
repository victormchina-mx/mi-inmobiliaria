<?php

namespace App\Filament\Resources\Clientes\RelationManagers;

use App\Filament\Resources\OrdenServicios\Tables\OrdenServiciosTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Historial de visitas del cliente (línea de tiempo de órdenes).
 */
class OrdenesRelationManager extends RelationManager
{
    protected static string $relationship = 'ordenes';

    protected static ?string $title = 'Historial de visitas';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return OrdenServiciosTable::historial($table);
    }
}
