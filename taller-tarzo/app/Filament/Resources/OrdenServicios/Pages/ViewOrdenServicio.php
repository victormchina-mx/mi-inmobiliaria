<?php

namespace App\Filament\Resources\OrdenServicios\Pages;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Actions\Ordenes\CambiarEtapa;
use App\Actions\Piso\EntregarVehiculo;
use App\Actions\Piso\RegistrarCalidad;
use App\Enums\EstadoMensaje;
use App\Enums\EventoMensaje;
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
use App\Services\Comunicacion\Notificador;
use App\Services\Cotizacion\Cotizador;
use App\Support\Checklists;
use App\Support\EnlacePortal;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

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

            Action::make('calidad')->label('Control de calidad')->icon(Heroicon::OutlinedCheckBadge)
                ->visible(fn () => $this->record->etapa->clave === Etapa::CALIDAD && auth()->user()?->can('piso.gestionar'))
                ->modalDescription('Si todo se cumple, el auto pasa a "Listo para entrega" y se avisa al cliente. Si no, regresa a corregir.')
                ->schema(fn (Schema $schema) => $schema->components([
                    CheckboxList::make('cumplidos')->label('Puntos verificados')->options(Checklists::CALIDAD)->bulkToggleable()->live(),
                    Select::make('regresar_a')->label('Si algo falla, regresa a')
                        ->options(Etapa::ordenadas()->where('es_final', false)->where('clave', '!=', Etapa::CALIDAD)->pluck('nombre', 'id'))
                        ->visible(fn (Get $get) => count($get('cumplidos') ?? []) < count(Checklists::CALIDAD)),
                    Textarea::make('observaciones')->label('Observaciones / qué corregir')->rows(2),
                ]))
                ->action(function (array $data) {
                    try {
                        $c = app(RegistrarCalidad::class)($this->record, $data['cumplidos'] ?? [], auth()->user(), $data['observaciones'] ?? null,
                            isset($data['regresar_a']) ? Etapa::find($data['regresar_a']) : null);
                    } catch (ValidationException $e) {
                        Notification::make()->title('No se registró')->body(collect($e->errors())->flatten()->implode(' '))->warning()->send();

                        return;
                    }
                    Notification::make()->title($c->aprobado ? 'Calidad aprobada: listo para entrega' : 'Regresa a corregir')->success()->send();
                    $this->refreshFormData(['etapa_id']);
                }),

            Action::make('entregar')->label('Entregar vehículo')->icon(Heroicon::OutlinedKey)->color('success')
                ->visible(fn () => $this->record->etapa->clave === Etapa::LISTO_ENTREGA && auth()->user()?->can('recepcion.gestionar'))
                ->modalWidth('2xl')
                ->schema(fn (Schema $schema) => $schema->components([
                    CheckboxList::make('cumplidos')->label('Checklist de entrega')->options(Checklists::ENTREGA)->bulkToggleable()->required(),
                    TextInput::make('kilometraje')->label('Kilometraje de salida')->numeric()->minValue(0)->suffix('km'),
                    Textarea::make('observaciones')->label('Observaciones')->rows(2),
                    TextInput::make('nombre')->label('Nombre de quien recibe')->required()->minLength(3)
                        ->default(fn () => $this->record->cliente->nombre_completo),
                    ViewField::make('firma')->label('Firma de conformidad')->view('filament.checkin.firma-pad'),
                ]))
                ->action(function (array $data) {
                    try {
                        app(EntregarVehiculo::class)($this->record, $data['cumplidos'] ?? [], (string) ($data['firma'] ?? ''), $data['nombre'], auth()->user(),
                            filled($data['kilometraje'] ?? null) ? (int) $data['kilometraje'] : null, $data['observaciones'] ?? null);
                    } catch (ValidationException $e) {
                        Notification::make()->title('No se registró la entrega')->body(collect($e->errors())->flatten()->implode(' '))->warning()->send();

                        return;
                    }
                    Notification::make()->title('Vehículo entregado')->success()->send();
                    $this->refreshFormData(['etapa_id', 'fecha_entrega']);
                }),

            Action::make('cambiarEtapa')->label('Cambiar etapa')->icon(Heroicon::OutlinedArrowRightCircle)->color('gray')
                ->visible(fn () => auth()->user()?->can('piso.gestionar') && ! $this->record->etapa->es_final)
                ->schema(fn (Schema $schema) => $schema->components([
                    Select::make('etapa_id')->label('Nueva etapa')->live()
                        ->options(Etapa::ordenadas()->where('es_final', false)->where('id', '!=', $this->record->etapa_id)->pluck('nombre', 'id'))
                        ->required(),
                    Text::make(fn (Get $get) => ($e = Etapa::find($get('etapa_id'))) ? implode(' ', CambiarEtapa::problemas($this->record, $e)) : '')->color('warning'),
                    Toggle::make('forzar')->label('Saltar las reglas de la etapa')->live()
                        ->visible(fn () => auth()->user()?->can('piso.forzar_etapa')),
                    Textarea::make('comentario')->label('Comentario')->rows(2)->maxLength(255)
                        ->required(fn (Get $get) => (bool) $get('forzar')),
                ]))
                ->action(function (array $data, OrdenServicio $record) {
                    try {
                        (new CambiarEtapa)($record, Etapa::findOrFail($data['etapa_id']), auth()->user(), $data['comentario'] ?? null, (bool) ($data['forzar'] ?? false));
                    } catch (ValidationException $e) {
                        Notification::make()->title('No se movió')->body(collect($e->errors())->flatten()->implode(' '))->warning()->send();

                        return;
                    }
                    Notification::make()->title('Etapa actualizada')->success()->send();
                    $this->refreshFormData(['etapa_id', 'etapa_desde']);
                }),

            ActionGroup::make([
                EditAction::make()->label('Editar datos de la orden'),
                Action::make('enlaceSeguimiento')->label('Enlace de seguimiento para el cliente')->icon(Heroicon::OutlinedLink)
                    ->visible(fn () => auth()->user()?->can('crm.gestionar') || $gestiona())
                    ->modalDescription('El cliente ve el avance, las fotos autorizadas y el presupuesto pendiente, sin crear cuenta. Vence en 90 días.')
                    ->schema([
                        TextInput::make('enlace')->label('Enlace')->readOnly()->copyable(copyMessage: 'Enlace copiado')
                            ->default(fn () => EnlacePortal::generar($this->record)),
                        Toggle::make('avisar')->label('Enviarlo al cliente (WhatsApp o correo, según su preferencia)')->default(true),
                    ])
                    ->modalSubmitActionLabel('Listo')
                    ->action(function (array $data) {
                        if (! ($data['avisar'] ?? false)) {
                            return;
                        }
                        $m = app(Notificador::class)->avisar(EventoMensaje::Ingreso, $this->record->cliente, $this->record);
                        $m->estado === EstadoMensaje::Omitido
                            ? Notification::make()->title('No se envió')->body($m->error)->warning()->send()
                            : Notification::make()->title('Enlace enviado al cliente')->success()->send();
                    }),
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
