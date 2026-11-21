<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Facturacion\FormularioCobro;
use App\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePagos extends ManageRecords
{
    protected static string $resource = PagoResource::class;

    protected function getHeaderActions(): array
    {
        return [FormularioCobro::accion()];
    }
}
