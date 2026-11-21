<?php

namespace App\Filament\Resources\Cotizaciones\Pages;

use App\Actions\Cotizacion\AgregarPartidas;
use App\Actions\Cotizacion\CrearSuplemento;
use App\Actions\Cotizacion\EnlaceAprobacion;
use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPartida;
use App\Filament\Resources\Cotizaciones\CotizacionResource;
use App\Filament\Resources\Cotizaciones\Support\ContextoFormulario;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Paquete;
use App\Services\Cotizacion\Cotizador;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class EditCotizacion extends EditRecord
{
    protected static string $resource = CotizacionResource::class;

    public function getTitle(): string
    {
        return "Cotización {$this->record->folio}";
    }

    public function getSubheading(): ?string
    {
        $r = $this->record;

        return $r->estado->getLabel().($r->enviada_at ? ' · enviada '.$r->enviada_at->diffForHumans() : '');
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrdenServicioResource::getUrl('view', ['record' => $this->record->orden]) => $this->record->orden->folio,
            CotizacionResource::getUrl() => 'Cotizaciones',
            $this->record->folio,
        ];
    }

    private function puedeGestionar(): bool
    {
        return (bool) auth()->user()?->can('cotizacion.gestionar');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->accionEnviar(),
            $this->accionRegistrarAutorizacion(),

            Action::make('suplemento')->label('Crear suplemento')->icon(Heroicon::OutlinedDocumentDuplicate)->color('gray')
                ->visible(fn () => $this->puedeGestionar() && ! $this->record->estado->editable() && $this->record->estado !== EstadoCotizacion::Reemplazada)
                ->requiresConfirmation()
                ->modalDescription('Se crea una nueva versión con todas las partidas. Las ya autorizadas se conservan y no se vuelven a pedir; agrega las nuevas y envíala.')
                ->action(function () {
                    $nueva = app(CrearSuplemento::class)($this->record, auth()->user());
                    Notification::make()->title("Suplemento {$nueva->folio} creado")->success()->send();
                    $this->redirect(CotizacionResource::getUrl('edit', ['record' => $nueva]));
                }),

            ActionGroup::make([
                Action::make('paquete')->label('Agregar paquete de servicio')->icon(Heroicon::OutlinedRectangleStack)
                    ->visible(fn () => $this->puedeGestionar() && $this->record->estado->editable())
                    ->schema(fn (Schema $schema) => $schema->components([
                        Select::make('paquete_id')->label('Paquete')->options(Paquete::where('activo', true)->pluck('nombre', 'id'))->required(),
                    ]))
                    ->action(function (array $data) {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        app(AgregarPartidas::class)->desdePaquete($this->record, Paquete::findOrFail($data['paquete_id']));
                        $this->recargar('Paquete agregado');
                    }),
                Action::make('pdf')->label('PDF del presupuesto')->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->url(fn () => route('cotizaciones.pdf', $this->record), shouldOpenInNewTab: true),
                Action::make('pdfValuacion')->label('PDF de valuación para la aseguradora')->icon(Heroicon::OutlinedDocumentText)
                    ->visible(fn () => $this->record->esSeguro())
                    ->url(fn () => route('cotizaciones.pdf', [$this->record, 'formato' => 'valuacion']), shouldOpenInNewTab: true),
                Action::make('orden')->label('Ir a la orden')->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->url(fn () => OrdenServicioResource::getUrl('view', ['record' => $this->record->orden])),
            ])->label('Más')->button()->color('gray'),
        ];
    }

    private function accionEnviar(): Action
    {
        $roles = fn () => $this->record->esSeguro() ? ['ajustador' => 'Ajustador (partidas de la aseguradora)', 'cliente' => 'Cliente (partidas a su cargo)'] : ['cliente' => 'Cliente'];

        return Action::make('enviar')->label(fn () => $this->record->estado === EstadoCotizacion::Enviada ? 'Reenviar enlace' : 'Enviar para autorizar')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->visible(fn () => $this->puedeGestionar() && in_array($this->record->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Enviada], true))
            ->modalHeading('Enviar para autorizar')
            ->modalDescription('Quien recibe el enlace ve cada partida con sus fotos y autoriza o rechaza sin crear cuenta. El enlace vence en '.EnlaceAprobacion::HORAS_VIGENCIA.' horas.')
            ->schema(fn (Schema $schema) => $schema->components([
                ToggleButtons::make('rol')->label('¿A quién?')->options($roles())->default(array_key_first($roles()))->inline()->live()->required()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('enlace', EnlaceAprobacion::generar($this->record, $state ?: 'cliente'))),
                TextInput::make('enlace')->label('Enlace')->readOnly()->copyable(copyMessage: 'Enlace copiado')
                    ->default(fn () => EnlaceAprobacion::generar($this->record, array_key_first($roles()))),
                CheckboxList::make('correo')->label('Enviar también por correo')
                    ->options(fn () => array_filter([
                        'cliente' => filled($this->record->orden->cliente->email) ? 'Cliente: '.$this->record->orden->cliente->email : null,
                        'ajustador' => $this->record->esSeguro() && filled($this->record->orden->siniestro?->ajustador_email) ? 'Ajustador: '.$this->record->orden->siniestro->ajustador_email : null,
                    ])),
                Text::make(fn (Get $get) => EnlaceAprobacion::telefono($this->record, (string) $get('rol'))
                    ? 'Al confirmar se abre WhatsApp con el mensaje listo.'
                    : 'No hay teléfono registrado para este destinatario: copia el enlace y envíalo por otro medio.'),
            ]))
            ->modalSubmitActionLabel('Marcar como enviada y abrir WhatsApp')
            ->action(function (array $data) {
                $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                try {
                    app(EnviarCotizacion::class)($this->record, $data['correo'] ?? []);
                } catch (ValidationException $e) {
                    Notification::make()->title('No se pudo enviar')->body(collect($e->errors())->flatten()->first())->danger()->send();

                    return;
                }

                $wa = EnlaceAprobacion::whatsapp($this->record->refresh(), $data['rol']);
                Notification::make()->title('Cotización enviada')->success()->send();

                $wa ? $this->redirect($wa) : $this->recargar();
            });
    }

    private function accionRegistrarAutorizacion(): Action
    {
        return Action::make('registrar')->label('Registrar autorización')->icon(Heroicon::OutlinedPhone)->color('gray')
            ->visible(fn () => $this->puedeGestionar() && $this->record->estado === EstadoCotizacion::Enviada)
            ->modalDescription('Úsalo cuando el cliente o el ajustador autoricen por teléfono o en persona. Queda registrado quién lo capturó.')
            ->schema(fn (Schema $schema) => $schema->components([
                ToggleButtons::make('rol')->label('Quién autoriza')
                    ->options($this->record->esSeguro() ? ['ajustador' => 'Ajustador', 'cliente' => 'Cliente'] : ['cliente' => 'Cliente'])
                    ->default($this->record->esSeguro() ? 'ajustador' : 'cliente')->inline()->live()->required(),
                TextInput::make('nombre')->label('Nombre de quien autoriza')->required()->minLength(3)
                    ->default(fn () => $this->record->orden->cliente->nombre_completo),
                CheckboxList::make('aprobadas')->label('Partidas autorizadas (las no marcadas quedan rechazadas)')
                    ->options(fn (Get $get) => ResponderCotizacion::partidasDelRol($this->record, (string) ($get('rol') ?: 'cliente'))
                        ->where('estado', EstadoPartida::Pendiente)
                        ->mapWithKeys(fn ($p) => [$p->id => "{$p->descripcion} · $".number_format($p->importe, 2)])->all())
                    ->bulkToggleable(),
                Textarea::make('comentario')->label('Comentario')->rows(2),
            ]))
            ->action(function (array $data) {
                $pendientes = ResponderCotizacion::partidasDelRol($this->record, $data['rol'])->where('estado', EstadoPartida::Pendiente);
                $aprobadas = array_map('intval', $data['aprobadas'] ?? []);
                $decisiones = $pendientes->mapWithKeys(fn ($p) => [$p->id => in_array($p->id, $aprobadas, true) ? 'aprobada' : 'rechazada'])->all();

                try {
                    app(ResponderCotizacion::class)($this->record, $data['rol'], $decisiones, $data['nombre'], $data['comentario'] ?? null, 'asesor', request()->ip(), request()->userAgent(), auth()->user());
                } catch (ValidationException $e) {
                    Notification::make()->title('No se registró')->body(collect($e->errors())->flatten()->first())->danger()->send();

                    return;
                }

                $this->recargar('Autorización registrada');
            });
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Guardar y recalcular')
            ->visible(fn () => $this->puedeGestionar() && $this->record->estado->editable());
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->visible(fn () => $this->record->estado->editable());
    }

    protected function afterSave(): void
    {
        ContextoFormulario::olvidar();
        app(Cotizador::class)->recalcular($this->record);
        $this->refreshFormData(['partidas']);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Cotización guardada y recalculada';
    }

    private function recargar(?string $mensaje = null): void
    {
        ContextoFormulario::olvidar();
        $this->record->refresh();
        $this->fillForm();

        if ($mensaje) {
            Notification::make()->title($mensaje)->success()->send();
        }
    }
}
