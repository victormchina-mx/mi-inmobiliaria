<?php

namespace App\Filament\Resources\OrdenesCompra\Pages;

use App\Enums\EstadoOrdenCompra;
use App\Filament\Resources\OrdenesCompra\OrdenCompraResource;
use App\Models\Proveedor;
use App\Support\GeneradorFolio;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdenCompra extends CreateRecord
{
    protected static string $resource = OrdenCompraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'folio' => GeneradorFolio::siguiente('OC'),
            'estado' => EstadoOrdenCompra::Borrador,
            'user_id' => auth()->id(),
            'fecha_estimada' => $data['fecha_estimada'] ?? today()->addDays(Proveedor::find($data['proveedor_id'])?->dias_entrega ?? 2),
        ];
    }

    protected function afterCreate(): void
    {
        $this->record->recalcularSubtotal();
    }

    protected function getRedirectUrl(): string
    {
        return OrdenCompraResource::getUrl('edit', ['record' => $this->record]);
    }
}
