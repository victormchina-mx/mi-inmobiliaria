<?php

namespace App\Filament\Resources\OrdenesCompra\Pages;

use App\Actions\Almacen\CambiarEstadoCompra;
use App\Actions\Almacen\RecibirMercancia;
use App\Enums\EstadoOrdenCompra;
use App\Filament\Resources\OrdenesCompra\OrdenCompraResource;
use App\Services\Inventario\LectorCfdi;
use App\Support\Media;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditOrdenCompra extends EditRecord
{
    protected static string $resource = OrdenCompraResource::class;

    public function getTitle(): string
    {
        return "Orden de compra {$this->record->folio}";
    }

    private function puede(): bool
    {
        return (bool) auth()->user()?->can('almacen.gestionar');
    }

    private function ejecutar(callable $fn, string $ok): void
    {
        try {
            $fn();
            Notification::make()->title($ok)->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title('No se pudo')->body(collect($e->errors())->flatten()->first())->danger()->send();
        }
        $this->record->refresh();
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        $estado = fn () => $this->record->estado;

        return [
            Action::make('enviar')->label('Enviar al proveedor')->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn () => $this->puede() && $estado() === EstadoOrdenCompra::Borrador)
                ->requiresConfirmation()
                ->modalDescription(fn () => $this->telefonoProveedor()
                    ? 'Se marca como enviada y se abre WhatsApp con el pedido listo para el proveedor.'
                    : 'Se marca como enviada. El proveedor no tiene teléfono: envíale el pedido por otro medio.')
                ->action(function () {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    try {
                        app(CambiarEstadoCompra::class)->enviar($this->record);
                    } catch (ValidationException $e) {
                        Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();

                        return;
                    }
                    Notification::make()->title('Orden de compra enviada')->success()->send();
                    ($wa = $this->whatsappPedido()) ? $this->redirect($wa) : $this->fillForm();
                }),

            $this->accionRecibir(),

            ActionGroup::make([
                Action::make('confirmar')->label('Proveedor confirmó')->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn () => $this->puede() && in_array($estado(), [EstadoOrdenCompra::Enviada, EstadoOrdenCompra::Confirmada], true))
                    ->schema(fn (Schema $schema) => $schema->components([
                        DatePicker::make('fecha_estimada')->label('Fecha de entrega confirmada')->native(false)->displayFormat('d/m/Y')
                            ->default(fn () => $this->record->fecha_estimada)->required(),
                    ]))
                    ->action(fn (array $data) => $this->ejecutar(fn () => app(CambiarEstadoCompra::class)->confirmar($this->record, $data['fecha_estimada']), 'Entrega confirmada')),
                Action::make('transito')->label('Va en camino')->icon(Heroicon::OutlinedTruck)
                    ->visible(fn () => $this->puede() && in_array($estado(), [EstadoOrdenCompra::Enviada, EstadoOrdenCompra::Confirmada], true))
                    ->action(fn () => $this->ejecutar(fn () => app(CambiarEstadoCompra::class)->enTransito($this->record), 'Marcada en tránsito')),
                Action::make('cancelar')->label('Cancelar orden de compra')->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn () => $this->puede() && in_array($estado(), [EstadoOrdenCompra::Borrador, EstadoOrdenCompra::Enviada, EstadoOrdenCompra::Confirmada, EstadoOrdenCompra::EnTransito], true))
                    ->requiresConfirmation()->modalDescription('Las piezas de órdenes de servicio vuelven a "por pedir".')
                    ->action(fn () => $this->ejecutar(fn () => app(CambiarEstadoCompra::class)->cancelar($this->record), 'Orden de compra cancelada')),
            ])->label('Más')->button()->color('gray'),
        ];
    }

    private function accionRecibir(): Action
    {
        return Action::make('recibir')->label('Recibir mercancía')->icon(Heroicon::OutlinedInboxArrowDown)->color('success')
            ->visible(fn () => $this->puede() && $this->record->estado->recibible())
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Registrar recepción')
            ->modalDescription('Sube el XML de la factura para tomar los costos reales automáticamente. Ajusta las cantidades si llegó incompleto.')
            ->fillForm(fn () => [
                'lineas' => $this->record->partidas()->get()->filter(fn ($p) => $p->pendiente() > 0)->map(fn ($p) => [
                    'id' => $p->id,
                    'descripcion' => $p->descripcion,
                    'numero_parte' => $p->numero_parte,
                    'cantidad' => $p->pendiente(),
                    'costo_real' => $p->costo_unitario,
                ])->values()->all(),
            ])
            ->schema(fn (Schema $schema) => $schema->components([
                FileUpload::make('factura')->label('Factura del proveedor (XML)')
                    ->disk(fn () => Media::nombreDisco())->directory('facturas-proveedor')->visibility('private')
                    ->acceptedFileTypes(['text/xml', 'application/xml'])->maxSize(1024)
                    ->live()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => $this->aplicarFactura($state, $get, $set)),
                Text::make(fn (Get $get) => $get('resumen_factura') ?? '')->color('info'),
                Hidden::make('resumen_factura'),
                Repeater::make('lineas')->label('Partidas pendientes')
                    ->schema([
                        Hidden::make('id'),
                        Hidden::make('numero_parte'),
                        TextInput::make('descripcion')->label('Partida')->readOnly()->columnSpan(3),
                        TextInput::make('cantidad')->label('Recibido')->numeric()->minValue(0),
                        TextInput::make('costo_real')->label('Costo unitario real')->numeric()->minValue(0)->prefix('$'),
                    ])
                    ->columns(5)->addable(false)->deletable(false)->reorderable(false),
                FileUpload::make('foto')->label('Foto de la mercancía o remisión')->image()
                    ->disk(fn () => Media::nombreDisco())->directory('recepciones')->visibility('private')
                    ->automaticallyResizeImagesToWidth((string) config('tarzo.media.max_foto_px')),
                Textarea::make('notas')->label('Notas')->rows(2),
            ]))
            ->action(function (array $data) {
                $lineas = collect($data['lineas'] ?? [])->mapWithKeys(fn ($l) => [$l['id'] => ['cantidad' => $l['cantidad'], 'costo_real' => $l['costo_real']]])->all();

                try {
                    app(RecibirMercancia::class)($this->record, $lineas, auth()->user(), $data['factura'] ?? null, $data['foto'] ?? null, $data['notas'] ?? null);
                } catch (ValidationException $e) {
                    Notification::make()->title('No se registró la recepción')->body(collect($e->errors())->flatten()->first())->danger()->send();

                    return;
                }

                Notification::make()->title('Mercancía recibida')->body('Inventario y órdenes de servicio actualizados.')->success()->send();
                $this->record->refresh();
                $this->fillForm();
            });
    }

    /**
     * Lee el XML recién subido y llena los costos reales de las partidas que coinciden.
     */
    private function aplicarFactura(mixed $state, Get $get, Set $set): void
    {
        $archivo = is_array($state) ? reset($state) : $state;

        if (! is_object($archivo) || ! method_exists($archivo, 'get')) {
            return;
        }

        try {
            $factura = app(LectorCfdi::class)->leer($archivo->get());
        } catch (Throwable) {
            $set('resumen_factura', 'No se pudo leer la factura: verifica que sea el XML del CFDI.');

            return;
        }

        $emparejadas = 0;
        foreach ($get('lineas') ?? [] as $clave => $linea) {
            if ($c = LectorCfdi::emparejar($factura['conceptos'], $linea['numero_parte'] ?? null, (string) $linea['descripcion'])) {
                $set("lineas.{$clave}.costo_real", $c['valor_unitario']);
                $emparejadas++;
            }
        }

        $set('resumen_factura', sprintf(
            'Factura %s de %s · total $%s · %d de %d partidas con costo tomado de la factura.',
            $factura['uuid'] ? substr($factura['uuid'], 0, 8).'…' : 'sin UUID',
            $factura['nombre_emisor'] ?? $factura['rfc_emisor'] ?? 'proveedor',
            number_format($factura['total'], 2),
            $emparejadas,
            count($get('lineas') ?? []),
        ));
    }

    private function telefonoProveedor(): ?string
    {
        $tel = preg_replace('/\D/', '', (string) $this->record->proveedor?->telefono);

        return $tel === '' ? null : (strlen($tel) === 10 ? '52'.$tel : $tel);
    }

    private function whatsappPedido(): ?string
    {
        $tel = $this->telefonoProveedor();

        if (! $tel) {
            return null;
        }

        $lineas = $this->record->partidas()->get()
            ->map(fn ($p) => '• '.rtrim(rtrim(number_format($p->cantidad, 3), '0'), '.').' × '.$p->descripcion.($p->numero_parte ? " ({$p->numero_parte})" : ''))
            ->implode("\n");

        $texto = "Pedido {$this->record->folio} de ".config('tarzo.taller.razon_social').":\n{$lineas}\n\n¿Nos confirma disponibilidad, precio y fecha de entrega?";

        return 'https://wa.me/'.$tel.'?text='.rawurlencode($texto);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->visible(fn () => $this->puede() && $this->record->estado->abierta());
    }

    protected function afterSave(): void
    {
        $this->record->recalcularSubtotal();
    }
}
