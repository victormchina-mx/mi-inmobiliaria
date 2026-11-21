<?php

namespace App\Filament\Facturacion;

use App\Actions\Facturacion\RegistrarPago;
use App\Enums\FormaPago;
use App\Models\Aseguradora;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\OrdenServicio;
use App\Models\Pago;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Acción "Registrar cobro" que usan Caja, Cuentas por cobrar, la orden y la factura.
 */
final class FormularioCobro
{
    /**
     * @param  callable(): array<string, mixed>|null  $valores  valores iniciales (pagador, orden, factura)
     */
    public static function accion(string $nombre = 'cobrar', ?callable $valores = null): Action
    {
        return Action::make($nombre)->label('Registrar cobro')->icon(Heroicon::OutlinedBanknotes)
            ->visible(fn () => auth()->user()?->can('facturacion.gestionar'))
            ->modalWidth('3xl')
            ->fillForm(function () use ($valores) {
                $v = $valores ? $valores() : [];
                $base = ['pagador' => 'cliente', 'fecha' => now(), 'forma_pago' => FormaPago::Efectivo->value, ...$v];
                if (! empty($v['factura_id']) && ($f = Factura::find($v['factura_id']))) {
                    $base['monto'] = $f->saldo;
                    $base['aplicar'] = [['factura_id' => $f->id, 'monto' => $f->saldo]];
                }

                return $base;
            })
            ->schema(self::campos())
            ->action(function (array $data, Action $action) {
                $aplicar = collect($data['aplicar'] ?? [])->filter(fn ($a) => filled($a['factura_id'] ?? null))
                    ->mapWithKeys(fn ($a) => [(int) $a['factura_id'] => (float) $a['monto']])->all();

                try {
                    [$pago, $errorRep] = app(RegistrarPago::class)([
                        'cliente_id' => $data['pagador'] === 'cliente' ? $data['cliente_id'] : null,
                        'aseguradora_id' => $data['pagador'] === 'aseguradora' ? $data['aseguradora_id'] : null,
                        'orden_servicio_id' => $data['orden_servicio_id'] ?? null,
                        'fecha' => $data['fecha'], 'forma_pago' => $data['forma_pago'], 'monto' => $data['monto'],
                        'referencia' => $data['referencia'] ?? null, 'notas' => $data['notas'] ?? null,
                    ], $aplicar, auth()->user());
                } catch (ValidationException $e) {
                    Notification::make()->title('No se registró el cobro')->body(collect($e->errors())->flatten()->first())->danger()->send();
                    $action->halt();

                    return;
                }

                $n = Notification::make()->title("Cobro {$pago->folio} registrado")
                    ->body($pago->por_aplicar > 0 ? 'Quedan $'.number_format($pago->por_aplicar, 2).' como anticipo / saldo a favor.' : null)
                    ->actions([Action::make('recibo')->label('Imprimir recibo')->url(route('caja.recibo', $pago), shouldOpenInNewTab: true)]);
                $errorRep
                    ? $n->warning()->body("El complemento de pago no se timbró: {$errorRep}. Reintenta desde Facturas.")->send()
                    : $n->success()->send();
            });
    }

    public static function campos(): array
    {
        return [
            ToggleButtons::make('pagador')->label('¿Quién paga?')->options(['cliente' => 'Cliente', 'aseguradora' => 'Aseguradora'])
                ->inline()->live()->required()->afterStateUpdated(fn (Set $set) => $set('aplicar', [])),
            Select::make('cliente_id')->label('Cliente')->searchable()
                ->getSearchResultsUsing(fn (string $search) => Cliente::buscar($search)->limit(20)->get()->mapWithKeys(fn ($c) => [$c->id => $c->nombre_completo]))
                ->getOptionLabelUsing(fn ($value) => Cliente::find($value)?->nombre_completo)
                ->visible(fn (Get $get) => $get('pagador') === 'cliente')->required(fn (Get $get) => $get('pagador') === 'cliente')->live(),
            Select::make('aseguradora_id')->label('Aseguradora')->options(fn () => Aseguradora::where('activo', true)->pluck('nombre', 'id'))
                ->visible(fn (Get $get) => $get('pagador') === 'aseguradora')->required(fn (Get $get) => $get('pagador') === 'aseguradora')->live(),
            Select::make('orden_servicio_id')->label('Orden (opcional)')
                ->options(fn (Get $get) => $get('cliente_id') ? OrdenServicio::where('cliente_id', $get('cliente_id'))->latest('id')->limit(30)->pluck('folio', 'id') : [])
                ->visible(fn (Get $get) => $get('pagador') === 'cliente'),
            DateTimePicker::make('fecha')->label('Fecha del pago')->required()->maxDate(now()->addDay()),
            Select::make('forma_pago')->label('Forma de pago')->options(FormaPago::deCobro())->required(),
            TextInput::make('monto')->label('Monto recibido')->numeric()->prefix('$')->minValue(0.01)->required(),
            TextInput::make('referencia')->label('Referencia (autorización, transferencia, cheque)')->maxLength(120),
            Repeater::make('aplicar')->label('Aplicar a facturas')->columnSpanFull()->defaultItems(0)->addActionLabel('Agregar factura')
                ->helperText('Lo que no se aplique queda como anticipo o saldo a favor. Las facturas PPD generan complemento de pago.')
                ->schema([
                    Select::make('factura_id')->label('Factura')->required()->distinct()
                        ->options(fn (Get $get) => self::facturasAbiertas($get('../../pagador'), $get('../../cliente_id'), $get('../../aseguradora_id')))
                        ->live()->afterStateUpdated(fn ($state, Set $set) => $set('monto', Factura::find($state)?->saldo)),
                    TextInput::make('monto')->label('Monto a aplicar')->numeric()->prefix('$')->minValue(0.01)->required(),
                ])->columns(2),
            Textarea::make('notas')->label('Notas')->rows(2)->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function facturasAbiertas(?string $pagador, $clienteId, $aseguradoraId): array
    {
        $q = Factura::porCobrar();
        $pagador === 'aseguradora' ? $q->where('aseguradora_id', $aseguradoraId ?: 0) : $q->where('cliente_id', $clienteId ?: 0);

        return $q->orderBy('timbrada_at')->get()
            ->mapWithKeys(fn (Factura $f) => [$f->id => "{$f->folio_completo} · {$f->metodo_pago} · saldo $".number_format($f->saldo, 2)])->all();
    }

    /**
     * "Aplicar a facturas" para un anticipo o saldo a favor ya registrado.
     */
    public static function aplicarAnticipo(): Action
    {
        return Action::make('aplicar')->label('Aplicar a facturas')->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (Pago $record) => ! $record->cancelado_at && $record->por_aplicar > 0.009 && auth()->user()?->can('facturacion.gestionar'))
            ->modalDescription(fn (Pago $record) => 'Disponible: $'.number_format($record->por_aplicar, 2))
            ->schema(fn (Pago $record) => [
                Repeater::make('aplicar')->label('Facturas')->defaultItems(1)->schema([
                    Select::make('factura_id')->label('Factura')->required()->distinct()
                        ->options(self::facturasAbiertas($record->aseguradora_id ? 'aseguradora' : 'cliente', $record->cliente_id, $record->aseguradora_id))
                        ->live()->afterStateUpdated(fn ($state, Set $set) => $set('monto', min($record->por_aplicar, (float) Factura::find($state)?->saldo))),
                    TextInput::make('monto')->label('Monto')->numeric()->prefix('$')->minValue(0.01)->required(),
                ])->columns(2),
            ])
            ->action(function (Pago $record, array $data, Action $action) {
                $aplicar = collect($data['aplicar'])->mapWithKeys(fn ($a) => [(int) $a['factura_id'] => (float) $a['monto']])->all();
                try {
                    $errorRep = app(RegistrarPago::class)->aplicarExistente($record, $aplicar);
                } catch (ValidationException $e) {
                    Notification::make()->title('No se aplicó')->body(collect($e->errors())->flatten()->first())->danger()->send();
                    $action->halt();

                    return;
                }
                $errorRep ? Notification::make()->title('Aplicado; el complemento de pago no se timbró')->body($errorRep)->warning()->send()
                    : Notification::make()->title('Pago aplicado')->success()->send();
            });
    }
}
