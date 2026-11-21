<?php

namespace App\Filament\Resources\OrdenServicios\Pages;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Actions\Ordenes\CambiarEtapa;
use App\Enums\TipoOrden;
use App\Filament\Actions\EnviarAFirmaAction;
use App\Filament\Resources\Checkins\CheckinResource;
use App\Filament\Resources\Cotizaciones\CotizacionResource;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Filament\Resources\OrdenServicios\Schemas\SiniestroForm;
use App\Jobs\GenerarPdfIngreso;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Models\Siniestro;
use App\Services\Cotizacion\Cotizador;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewOrdenServicio extends ViewRecord
{
    protected static string $resource = OrdenServicioResource::class;

    public function getTitle(): string
    {
        return "Orden {$this->record->folio}";
    }

    protected function getHeaderActions(): array
    {
        $gestiona = fn () => auth()->user()?->can('recepcion.gestionar');

        return [
            Action::make('checkin')
                ->label(fn () => $this->record->checkin ? 'Ver check-in' : 'Hacer check-in')
                ->icon(Heroicon::OutlinedCamera)
                ->action(fn () => $this->redirect(CheckinResource::urlParaOrden($this->record)))
                ->visible($gestiona),

            EnviarAFirmaAction::make(fn () => $this->record->checkin),

            Action::make('cotizar')
                ->label(fn () => $this->record->cotizacionActual ? 'Ver cotización' : 'Cotizar')
                ->icon(Heroicon::OutlinedCalculator)
                ->visible(fn () => auth()->user()?->can('cotizacion.gestionar') || ($this->record->cotizacionActual && auth()->user()?->can('cotizacion.ver')))
                ->action(function () {
                    $cotizacion = $this->record->cotizacionActual ?? app(AbrirCotizacion::class)($this->record, auth()->user());
                    $this->redirect(CotizacionResource::getUrl('edit', ['record' => $cotizacion]));
                }),

            Action::make('siniestro')->label('Siniestro')->icon(Heroicon::OutlinedShieldCheck)->color('gray')
                ->visible(fn () => $this->record->tipo === TipoOrden::Seguro && auth()->user()?->can('cotizacion.gestionar'))
                ->modalHeading(fn () => 'Expediente del siniestro · '.$this->record->aseguradora?->nombre)
                ->modalWidth('4xl')
                ->fillForm(fn () => $this->record->siniestro?->toArray() ?? [
                    'numero' => $this->record->numero_siniestro,
                    'deducible_tipo' => 'monto',
                    'deducible_valor' => $this->record->poliza?->deducible_monto ?? 0,
                ])
                ->schema(fn (Schema $schema) => $schema->components(SiniestroForm::campos()))
                ->action(function (array $data) {
                    Siniestro::updateOrCreate(
                        ['orden_servicio_id' => $this->record->id],
                        [...$data, 'aseguradora_id' => $this->record->aseguradora_id],
                    );
                    $this->record->update(['numero_siniestro' => $data['numero']]);

                    // Recalcula el borrador para reflejar deducible, demérito y salvamento.
                    if (($c = $this->record->cotizacionActual) && $c->estado->editable()) {
                        app(Cotizador::class)->recalcular($c);
                    }

                    Notification::make()->title('Siniestro guardado')->success()->send();
                }),

            Action::make('pdf')->label('PDF de ingreso')->icon(Heroicon::OutlinedDocumentArrowDown)->color('gray')
                ->visible(fn () => $this->record->checkin !== null)
                ->url(fn () => route('ordenes.pdf-ingreso', $this->record), shouldOpenInNewTab: true),

            Action::make('cambiarEtapa')->label('Cambiar etapa')->icon(Heroicon::OutlinedArrowRightCircle)
                ->visible(fn () => auth()->user()?->can('piso.gestionar'))
                ->schema(fn (Schema $schema) => $schema->components([
                    Select::make('etapa_id')->label('Nueva etapa')
                        ->options(Etapa::ordenadas()->where('id', '!=', $this->record->etapa_id)->pluck('nombre', 'id'))
                        ->required(),
                    Textarea::make('comentario')->label('Comentario')->rows(2)->maxLength(255),
                ]))
                ->action(function (array $data, OrdenServicio $record) {
                    (new CambiarEtapa)($record, Etapa::findOrFail($data['etapa_id']), auth()->user(), $data['comentario'] ?? null);
                    Notification::make()->title('Etapa actualizada')->success()->send();
                    $this->refreshFormData(['etapa_id', 'etapa_desde']);
                }),

            ActionGroup::make([
                EditAction::make()->label('Editar datos de la orden'),
                Action::make('reenviarPdf')->label('Reenviar PDF al cliente por correo')->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn () => $gestiona() && $this->record->checkin?->estaFirmado() && filled($this->record->cliente->email))
                    ->requiresConfirmation()
                    ->action(function () {
                        GenerarPdfIngreso::dispatch($this->record->checkin->id, enviarAlCliente: true);
                        Notification::make()->title('El PDF se enviará en unos momentos a '.$this->record->cliente->email)->success()->send();
                    }),
            ]),
        ];
    }
}
