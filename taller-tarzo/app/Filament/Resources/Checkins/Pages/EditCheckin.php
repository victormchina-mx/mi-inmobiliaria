<?php

namespace App\Filament\Resources\Checkins\Pages;

use App\Actions\Recepcion\FirmarCheckin;
use App\Filament\Actions\EnviarAFirmaAction;
use App\Filament\Resources\Checkins\CheckinResource;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class EditCheckin extends EditRecord
{
    protected static string $resource = CheckinResource::class;

    public function getTitle(): string
    {
        return 'Check-in · '.$this->record->orden->folio;
    }

    public function getSubheading(): ?string
    {
        $o = $this->record->orden;

        return "{$o->vehiculo->descripcion} — {$o->cliente->nombre_completo}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrdenServicioResource::getUrl() => 'Órdenes de servicio',
            OrdenServicioResource::getUrl('view', ['record' => $this->record->orden]) => $this->record->orden->folio,
            'Check-in',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            EnviarAFirmaAction::make(fn () => $this->record),
            Action::make('pdf')->label('PDF de ingreso')->icon(Heroicon::OutlinedDocumentArrowDown)->color('gray')
                ->url(fn () => route('ordenes.pdf-ingreso', $this->record->orden), shouldOpenInNewTab: true),
            Action::make('orden')->label('Ir a la orden')->color('gray')
                ->url(fn () => OrdenServicioResource::getUrl('view', ['record' => $this->record->orden])),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar inventario')
            ->visible(fn () => ! $this->record->estaFirmado());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Los campos de firma no son columnas: se precarga el nombre del cliente.
        $data['firma_nombre'] = $this->record->orden->cliente->nombre_completo;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['completado_at'] = $this->record->completado_at ?? now();

        return $data;
    }

    protected function afterSave(): void
    {
        $estado = $this->form->getRawState();
        $firma = $estado['firma_tableta'] ?? null;

        if (blank($firma)) {
            return;
        }

        try {
            app(FirmarCheckin::class)(
                $this->record->refresh(),
                $firma,
                (string) ($estado['firma_nombre'] ?? ''),
                (bool) ($estado['firma_acepta_aviso'] ?? false),
                (bool) ($estado['firma_acepta_condiciones'] ?? false),
                'tableta',
                request()->ip(),
                request()->userAgent(),
            );
        } catch (ValidationException $e) {
            Notification::make()->title('El inventario se guardó, pero la firma no')
                ->body(collect($e->errors())->flatten()->first())->danger()->persistent()->send();

            return;
        }

        Notification::make()->title('Inventario firmado')
            ->body(filled($this->record->orden->cliente->email) ? 'Se enviará el PDF al correo del cliente.' : 'El cliente no tiene correo; descarga el PDF para entregárselo.')
            ->success()->send();

        $this->redirect(OrdenServicioResource::getUrl('view', ['record' => $this->record->orden]));
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Inventario guardado';
    }
}
