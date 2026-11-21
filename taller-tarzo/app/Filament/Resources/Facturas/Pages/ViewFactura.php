<?php

namespace App\Filament\Resources\Facturas\Pages;

use App\Actions\Facturacion\CrearNotaCredito;
use App\Actions\Facturacion\CrearSustituta;
use App\Enums\EstadoFactura;
use App\Enums\MotivoCancelacion;
use App\Filament\Facturacion\FormularioCobro;
use App\Filament\Resources\Facturas\FacturaResource;
use App\Mail\FacturaCorreo;
use App\Models\Factura;
use App\Services\Cfdi\Facturador;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ViewFactura extends ViewRecord
{
    protected static string $resource = FacturaResource::class;

    public function getTitle(): string
    {
        return "{$this->record->etiquetaTipo()} {$this->record->folio_completo}";
    }

    protected function getHeaderActions(): array
    {
        $gestiona = fn () => auth()->user()?->can('facturacion.gestionar');
        $f = fn (): Factura => $this->record;

        return [
            EditAction::make()->label('Revisar datos'),

            Action::make('timbrar')->label('Timbrar')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                ->visible(fn () => $gestiona() && $f()->editable())
                ->requiresConfirmation()
                ->modalHeading(fn () => 'Timbrar '.$f()->folio_completo)
                ->modalDescription(fn () => 'Se envía al SAT por medio del PAC por $'.number_format($f()->total, 2).' a '.$f()->receptor_nombre.'. Después ya no se puede modificar, solo cancelar.'
                    .(config('tarzo.cfdi.driver') === 'simulado' ? ' (Modo simulado: sin validez fiscal.)' : ''))
                ->action(function () use ($f) {
                    try {
                        app(Facturador::class)->timbrar($f());
                    } catch (ValidationException $e) {
                        Notification::make()->title('El PAC rechazó la factura')->body(collect($e->errors())->flatten()->first())->danger()->persistent()->send();
                        $this->refreshFormData([]);

                        return;
                    }
                    Notification::make()->title('Factura timbrada')->body('UUID '.$f()->refresh()->uuid)->success()->send();
                    if (filled($f()->email)) {
                        Mail::to($f()->email)->queue(new FacturaCorreo($f()));
                    }
                    $this->redirect(FacturaResource::getUrl('view', ['record' => $f()]));
                }),

            FormularioCobro::accion('cobrar', fn () => [
                'pagador' => $f()->receptor_tipo, 'cliente_id' => $f()->cliente_id, 'aseguradora_id' => $f()->aseguradora_id,
                'orden_servicio_id' => $f()->orden_servicio_id, 'factura_id' => $f()->id,
            ])->visible(fn () => $gestiona() && $f()->tipo === Factura::INGRESO && $f()->timbrada() && $f()->saldo > 0),

            ActionGroup::make([
                Action::make('xml')->label('Descargar XML')->icon(Heroicon::OutlinedCodeBracket)
                    ->visible(fn () => filled($f()->xml_path))->url(fn () => route('facturas.archivo', [$f(), 'xml'])),
                Action::make('pdf')->label('Descargar PDF')->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->visible(fn () => filled($f()->pdf_path))->url(fn () => route('facturas.archivo', [$f(), 'pdf']), shouldOpenInNewTab: true),
                Action::make('correo')->label('Enviar por correo')->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn () => $f()->timbrada() || $f()->estado === EstadoFactura::Cancelada)
                    ->schema([TextInput::make('email')->label('Correo')->email()->required()->default(fn () => $f()->email)])
                    ->action(function (array $data) use ($f) {
                        Mail::to($data['email'])->queue(new FacturaCorreo($f()));
                        Notification::make()->title('Enviada a '.$data['email'])->success()->send();
                    }),
                Action::make('notaCredito')->label('Nota de crédito')->icon(Heroicon::OutlinedReceiptRefund)
                    ->visible(fn () => $gestiona() && $f()->tipo === Factura::INGRESO && $f()->timbrada() && $f()->saldo > 0)
                    ->modalDescription(fn () => 'Descuento, bonificación o devolución sobre esta factura. Saldo actual: $'.number_format($f()->saldo, 2))
                    ->schema([
                        TextInput::make('monto')->label('Monto con IVA')->numeric()->prefix('$')->minValue(0.01)->required(),
                        TextInput::make('motivo')->label('Concepto')->required()->maxLength(200)->placeholder('Bonificación por retraso en la entrega'),
                    ])
                    ->action(function (array $data) use ($f) {
                        try {
                            $nota = app(CrearNotaCredito::class)($f(), (float) $data['monto'], $data['motivo'], auth()->user());
                        } catch (ValidationException $e) {
                            Notification::make()->title('No se creó')->body(collect($e->errors())->flatten()->first())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Nota de crédito en borrador: revísala y tímbrala')->success()->send();
                        $this->redirect(FacturaResource::getUrl('view', ['record' => $nota]));
                    }),
                Action::make('sustituir')->label('Corregir (sustituir)')->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->visible(fn () => $gestiona() && $f()->tipo === Factura::INGRESO && $f()->timbrada())
                    ->requiresConfirmation()
                    ->modalDescription('Se crea una copia en borrador relacionada (04) para corregir datos. Al timbrarla, cancela esta con el motivo 01.')
                    ->action(function () use ($f) {
                        $nueva = app(CrearSustituta::class)($f(), auth()->user());
                        $this->redirect(FacturaResource::getUrl('edit', ['record' => $nueva]));
                    }),
                Action::make('cancelar')->label('Cancelar ante el SAT')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn () => $gestiona() && $f()->timbrada())
                    ->modalDescription('Desde 2022 la cancelación requiere motivo. Si la factura es mayor a $1,000 y tiene más de 24 h, el receptor debe aceptarla.')
                    ->schema([
                        Select::make('motivo')->label('Motivo')->options(MotivoCancelacion::class)->required()->live(),
                        Select::make('sustituta_id')->label('Factura que la sustituye')
                            ->options(fn () => Factura::where('factura_relacionada_id', $f()->id)->where('relacion_tipo', '04')->where('estado', EstadoFactura::Timbrada->value)->get()->pluck('folio_completo', 'id'))
                            ->visible(fn (Get $get) => in_array($get('motivo'), ['01', MotivoCancelacion::ConErrorConRelacion], true))
                            ->required(fn (Get $get) => in_array($get('motivo'), ['01', MotivoCancelacion::ConErrorConRelacion], true)),
                    ])
                    ->action(function (array $data) use ($f) {
                        $motivo = $data['motivo'] instanceof MotivoCancelacion ? $data['motivo']->value : $data['motivo'];
                        try {
                            app(Facturador::class)->cancelar($f(), $motivo, isset($data['sustituta_id']) ? Factura::find($data['sustituta_id']) : null);
                        } catch (ValidationException $e) {
                            Notification::make()->title('No se canceló')->body(collect($e->errors())->flatten()->first())->danger()->send();

                            return;
                        }
                        $f()->refresh()->estado === EstadoFactura::Cancelada
                            ? Notification::make()->title('Factura cancelada')->success()->send()
                            : Notification::make()->title('Cancelación en proceso')->body('El receptor debe aceptarla en el buzón tributario.')->warning()->send();
                        $this->redirect(FacturaResource::getUrl('view', ['record' => $f()]));
                    }),
            ])->label('Más')->button()->color('gray'),
        ];
    }
}
