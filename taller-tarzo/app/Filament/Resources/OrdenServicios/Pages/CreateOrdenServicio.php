<?php

namespace App\Filament\Resources\OrdenServicios\Pages;

use App\Actions\Ordenes\AbrirOrden;
use App\Enums\TipoOrden;
use App\Filament\Resources\Checkins\CheckinResource;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\User;
use App\Models\Vehiculo;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrdenServicio extends CreateRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Recibir vehículo';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Abrir orden y hacer check-in');
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        // Llega desde la ficha del vehículo: /panel/ordenes/recibir?vehiculo=ID
        if ($vehiculo = Vehiculo::find(request()->integer('vehiculo'))) {
            $poliza = $vehiculo->polizas()->latest('vigencia_fin')->first();
            $this->form->fill([
                ...$this->form->getRawState(),
                'vehiculo_id' => $vehiculo->id,
                'kilometraje_ingreso' => $vehiculo->kilometraje,
                'poliza_id' => $poliza?->id,
                'aseguradora_id' => $poliza?->aseguradora_id,
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
        $tipo = $data['tipo'] instanceof TipoOrden ? $data['tipo'] : TipoOrden::from($data['tipo']);
        $asesor = isset($data['asesor_id']) ? User::find($data['asesor_id']) : auth()->user();

        unset($data['vehiculo_id'], $data['tipo'], $data['asesor_id']);

        return (new AbrirOrden)($vehiculo, $tipo, $asesor, $data);
    }

    protected function getRedirectUrl(): string
    {
        // Siguiente paso natural: el inventario de ingreso.
        return CheckinResource::urlParaOrden($this->record);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Orden {$this->record->folio} abierta. Ahora haz el inventario de ingreso.";
    }
}
