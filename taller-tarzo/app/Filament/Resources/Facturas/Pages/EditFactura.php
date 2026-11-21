<?php

namespace App\Filament\Resources\Facturas\Pages;

use App\Filament\Resources\Facturas\FacturaResource;
use Filament\Resources\Pages\EditRecord;

class EditFactura extends EditRecord
{
    protected static string $resource = FacturaResource::class;

    public function getTitle(): string
    {
        return "Revisar {$this->record->etiquetaTipo()} {$this->record->folio_completo}";
    }

    protected function getRedirectUrl(): string
    {
        return FacturaResource::getUrl('view', ['record' => $this->record]);
    }
}
