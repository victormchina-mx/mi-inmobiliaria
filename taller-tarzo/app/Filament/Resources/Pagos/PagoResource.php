<?php

namespace App\Filament\Resources\Pagos;

use App\Actions\Facturacion\CancelarPago;
use App\Enums\FormaPago;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Facturacion\FormularioCobro;
use App\Filament\Resources\Pagos\Pages\ManagePagos;
use App\Models\Pago;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Caja: cobros recibidos (efectivo, tarjeta, transferencia), anticipos y recibos.
 */
class PagoResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'facturacion';

    protected static ?string $model = Pago::class;

    protected static ?string $slug = 'caja';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Caja';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'cobro';

    protected static ?string $pluralModelLabel = 'Caja';

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['cliente', 'aseguradora', 'orden', 'aplicaciones.factura']))
            ->columns([
                TextColumn::make('folio')->label('Recibo')->searchable()->description(fn (Pago $r) => $r->fecha->format('d/m/Y H:i')),
                TextColumn::make('pagador')->label('Pagó')->state(fn (Pago $r) => $r->pagador())
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('cliente', fn ($c) => $c->buscar($search))->orWhereHas('aseguradora', fn ($a) => $a->where('nombre', 'like', "%{$search}%"))),
                TextColumn::make('forma_pago')->label('Forma')->badge()->color('gray'),
                TextColumn::make('monto')->label('Monto')->money('MXN')->sortable()->summarize(Sum::make()->money('MXN')->label('Total')),
                TextColumn::make('aplicado')->label('Aplicado a')->wrap()
                    ->state(fn (Pago $r) => $r->aplicaciones->map(fn ($a) => $a->factura->folio_completo)->implode(', ') ?: null)->placeholder('—'),
                TextColumn::make('por_aplicar')->label('Anticipo / a favor')->money('MXN')->color(fn ($state) => $state > 0 ? 'warning' : null),
                TextColumn::make('orden.folio')->label('Orden')->placeholder('—'),
                TextColumn::make('cancelado_at')->label('Estado')->badge()
                    ->state(fn (Pago $r) => $r->cancelado_at ? 'Cancelado' : 'Vigente')->color(fn ($state) => $state === 'Cancelado' ? 'danger' : 'success'),
            ])
            ->filters([
                Filter::make('hoy')->label('Solo hoy')->query(fn ($query) => $query->whereDate('fecha', today())),
                SelectFilter::make('forma_pago')->label('Forma')->options(FormaPago::deCobro()),
                TernaryFilter::make('anticipos')->label('Con anticipo por aplicar')
                    ->queries(true: fn ($query) => $query->where('por_aplicar', '>', 0)->whereNull('cancelado_at'), false: fn ($query) => $query->where('por_aplicar', 0)),
                TernaryFilter::make('cancelado_at')->label('Cancelados')->nullable(),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                Action::make('recibo')->label('Recibo')->icon(Heroicon::OutlinedPrinter)->url(fn (Pago $r) => route('caja.recibo', $r), shouldOpenInNewTab: true),
                FormularioCobro::aplicarAnticipo(),
                Action::make('cancelar')->label('Cancelar')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn (Pago $r) => ! $r->cancelado_at && auth()->user()?->can('facturacion.gestionar'))
                    ->modalDescription('Devuelve el saldo a las facturas y cancela el complemento de pago, si lo hay.')
                    ->schema([TextInput::make('motivo')->label('Motivo')->required()->maxLength(200)])
                    ->action(function (Pago $r, array $data) {
                        try {
                            app(CancelarPago::class)($r, $data['motivo']);
                        } catch (ValidationException $e) {
                            Notification::make()->title('No se canceló')->body(collect($e->errors())->flatten()->first())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Cobro cancelado')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePagos::route('/')];
    }
}
