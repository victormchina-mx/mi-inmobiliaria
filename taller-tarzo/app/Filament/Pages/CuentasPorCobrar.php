<?php

namespace App\Filament\Pages;

use App\Filament\Facturacion\FormularioCobro;
use App\Filament\Resources\Facturas\FacturaResource;
use App\Models\Factura;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Cartera: facturas timbradas con saldo, por aseguradora, flotilla o particulares, con antigüedad de saldos.
 */
class CuentasPorCobrar extends Page implements HasTable
{
    use InteractsWithTable;

    public const RANGOS = ['0-30' => 'Al corriente / 0–30', '31-60' => '31–60 días', '61-90' => '61–90 días', '90+' => 'Más de 90'];

    protected string $view = 'filament.pages.cuentas-por-cobrar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Cuentas por cobrar';

    protected static ?string $slug = 'cuentas-por-cobrar';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('facturacion.ver');
    }

    public static function grupo(Factura $f): string
    {
        return $f->aseguradora?->nombre ?? ($f->cliente?->flotilla ? 'Flotilla '.$f->cliente->flotilla->nombre : 'Particulares');
    }

    public static function rango(Factura $f): string
    {
        $d = $f->diasVencida();

        return match (true) {
            $d <= 30 => '0-30',
            $d <= 60 => '31-60',
            $d <= 90 => '61-90',
            default => '90+',
        };
    }

    /**
     * @return Collection<string, array<string, float>> grupo => [rango => saldo, total]
     */
    public function getAntiguedad(): Collection
    {
        return Factura::porCobrar()->with(['aseguradora', 'cliente.flotilla'])->get()
            ->groupBy(fn (Factura $f) => self::grupo($f))
            ->map(function ($facturas) {
                $fila = array_fill_keys(array_keys(self::RANGOS), 0.0);
                foreach ($facturas as $f) {
                    $fila[self::rango($f)] += $f->saldo;
                }

                return [...$fila, 'total' => array_sum($fila), 'n' => $facturas->count()];
            })
            ->sortByDesc('total');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Factura::porCobrar()->with(['aseguradora', 'cliente.flotilla', 'orden']))
            ->columns([
                TextColumn::make('grupo')->label('Cartera')->state(fn (Factura $r) => self::grupo($r)),
                TextColumn::make('receptor_nombre')->label('Receptor')->searchable(['receptor_nombre', 'receptor_rfc'])->wrap()
                    ->description(fn (Factura $r) => $r->orden?->numero_siniestro ? 'Siniestro '.$r->orden->numero_siniestro : $r->orden?->folio),
                TextColumn::make('folio')->label('Factura')->formatStateUsing(fn (Factura $r) => $r->folio_completo)
                    ->url(fn (Factura $r) => FacturaResource::getUrl('view', ['record' => $r])),
                TextColumn::make('total')->label('Total')->money('MXN'),
                TextColumn::make('saldo')->label('Saldo')->money('MXN')->sortable()->weight('bold')
                    ->summarize(Sum::make()->money('MXN')->label('Cartera')),
                TextColumn::make('vence')->label('Vence')->date('d/m/Y')->placeholder('Contado')->sortable()
                    ->description(fn (Factura $r) => $r->diasVencida() > 0 ? "{$r->diasVencida()} días vencida" : null)
                    ->color(fn (Factura $r) => match (self::rango($r)) {
                        '0-30' => null, '31-60' => 'warning', default => 'danger'
                    }),
                TextColumn::make('contrarrecibo')->label('Contrarrecibo')->placeholder('—')
                    ->description(fn (Factura $r) => $r->fecha_pago_compromiso ? 'Pago comprometido '.$r->fecha_pago_compromiso->format('d/m/Y') : null),
            ])
            ->filters([
                SelectFilter::make('receptor_tipo')->label('Tipo')->options(['aseguradora' => 'Aseguradoras', 'cliente' => 'Clientes y flotillas']),
                SelectFilter::make('aseguradora_id')->label('Aseguradora')->relationship('aseguradora', 'nombre'),
                SelectFilter::make('rango')->label('Antigüedad')->options(self::RANGOS)
                    ->query(function ($query, array $data) {
                        $base = 'COALESCE(vence, DATE(timbrada_at))';
                        $limite = fn (int $dias) => today()->subDays($dias)->toDateString();

                        return match ($data['value'] ?? null) {
                            '0-30' => $query->whereRaw("{$base} >= ?", [$limite(30)]),
                            '31-60' => $query->whereRaw("{$base} < ? and {$base} >= ?", [$limite(30), $limite(60)]),
                            '61-90' => $query->whereRaw("{$base} < ? and {$base} >= ?", [$limite(60), $limite(90)]),
                            '90+' => $query->whereRaw("{$base} < ?", [$limite(90)]),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('vence')
            ->recordActions([
                Action::make('seguimiento')->label('Seguimiento')->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn () => auth()->user()?->can('facturacion.gestionar'))
                    ->fillForm(fn (Factura $r) => $r->only(['contrarrecibo', 'fecha_pago_compromiso', 'notas']))
                    ->schema([
                        TextInput::make('contrarrecibo')->label('Número de contrarrecibo')->maxLength(60),
                        DatePicker::make('fecha_pago_compromiso')->label('Fecha de pago comprometida'),
                        Textarea::make('notas')->label('Notas de cobranza')->rows(3),
                    ])
                    ->action(fn (Factura $r, array $data) => $r->update($data)),
                FormularioCobro::accion('cobrar', null)
                    ->fillForm(fn (Factura $record) => [
                        'pagador' => $record->receptor_tipo, 'cliente_id' => $record->cliente_id, 'aseguradora_id' => $record->aseguradora_id,
                        'orden_servicio_id' => $record->orden_servicio_id, 'fecha' => now(), 'forma_pago' => '03',
                        'monto' => $record->saldo, 'aplicar' => [['factura_id' => $record->id, 'monto' => $record->saldo]],
                    ]),
            ]);
    }
}
