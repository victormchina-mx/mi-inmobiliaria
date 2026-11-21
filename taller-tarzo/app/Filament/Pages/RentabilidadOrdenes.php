<?php

namespace App\Filament\Pages;

use App\Enums\TipoOrden;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\CierreOrden;
use App\Models\Parametro;
use App\Services\Finanzas\Rentabilidad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Utilidad de cada orden entregada: ingreso contra costos reales.
 */
class RentabilidadOrdenes extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.tabla-simple';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Utilidad por orden';

    protected static ?string $slug = 'utilidad-por-orden';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('reportes.ver');
    }

    public function table(Table $table): Table
    {
        $minimo = Parametro::valor('margen_minimo', 25);

        return $table
            ->query(CierreOrden::query()->with('orden.vehiculo', 'orden.cliente')->join('ordenes_servicio', 'ordenes_servicio.id', '=', 'cierres_orden.orden_servicio_id')->select('cierres_orden.*'))
            ->columns([
                TextColumn::make('orden.folio')->label('Orden')->searchable(query: fn ($query, string $search) => $query->where('ordenes_servicio.folio', 'like', "%{$search}%"))
                    ->description(fn (CierreOrden $r) => trim("{$r->orden->vehiculo->marca} {$r->orden->vehiculo->modelo} · {$r->orden->cliente->nombre_completo}"))
                    ->url(fn (CierreOrden $r) => OrdenServicioResource::getUrl('view', ['record' => $r->orden_servicio_id])),
                TextColumn::make('orden.tipo')->label('Tipo')->badge(),
                TextColumn::make('orden.fecha_entrega')->label('Entrega')->date('d/m/Y'),
                TextColumn::make('ingreso')->label('Ingreso')->money('MXN')->sortable()->description(fn (CierreOrden $r) => $r->ingreso_estimado ? 'estimado' : null)
                    ->summarize(Sum::make()->money('MXN')->label('')),
                TextColumn::make('costo_refacciones')->label('Refacciones')->money('MXN')->toggleable(),
                TextColumn::make('costo_materiales')->label('Materiales')->money('MXN')->toggleable(),
                TextColumn::make('costo_mano_obra')->label('Mano de obra')->money('MXN')->toggleable()
                    ->description(fn (CierreOrden $r) => number_format($r->horas_reales, 1).' h de '.number_format($r->horas_estandar, 1)),
                TextColumn::make('costo_subcontratos')->label('Subcontratos')->money('MXN')->toggleable(),
                TextColumn::make('utilidad')->label('Utilidad')->money('MXN')->sortable()->weight('bold')->summarize(Sum::make()->money('MXN')->label('')),
                TextColumn::make('margen')->label('Margen')->sortable()->badge()
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format($state, 1).' %')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state < $minimo ? 'danger' : 'success')),
            ])
            ->filters([
                Filter::make('entrega')->schema([
                    DatePicker::make('desde')->label('Entregadas desde')->default(now()->startOfMonth()),
                    DatePicker::make('hasta')->label('Hasta'),
                ])->query(fn ($query, array $data) => $query
                    ->when($data['desde'] ?? null, fn ($q, $v) => $q->whereDate('ordenes_servicio.fecha_entrega', '>=', $v))
                    ->when($data['hasta'] ?? null, fn ($q, $v) => $q->whereDate('ordenes_servicio.fecha_entrega', '<=', $v))),
                SelectFilter::make('tipo')->label('Tipo')->options(TipoOrden::class)
                    ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($q, $v) => $q->where('ordenes_servicio.tipo', $v))),
                TernaryFilter::make('bajo')->label("Margen bajo (< {$minimo} %)")
                    ->queries(true: fn ($query) => $query->where('margen', '<', $minimo), false: fn ($query) => $query->where('margen', '>=', $minimo)),
            ])
            ->defaultSort('ordenes_servicio.fecha_entrega', 'desc')
            ->recordActions([
                Action::make('recalcular')->label('Recalcular')->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (CierreOrden $r) {
                        app(Rentabilidad::class)->cerrar($r->orden, auth()->user());
                        Notification::make()->title('Utilidad recalculada')->success()->send();
                    }),
            ]);
    }
}
