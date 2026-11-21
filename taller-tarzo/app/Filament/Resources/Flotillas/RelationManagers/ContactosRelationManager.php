<?php

namespace App\Filament\Resources\Flotillas\RelationManagers;

use App\Filament\Resources\Clientes\RelationManagers\ContactosRelationManager as Base;

/**
 * Contactos de la flotilla autorizados para aprobar presupuestos.
 */
class ContactosRelationManager extends Base
{
    protected static string $relationship = 'contactos';

    protected static ?string $title = 'Contactos autorizados';
}
