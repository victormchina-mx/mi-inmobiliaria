<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\EncuestaNps;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * NPS del taller: indicador general, por asesor y por aseguradora, y seguimiento de detractores.
 */
class Satisfaccion extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.satisfaccion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Satisfacción (NPS)';

    protected static ?string $slug = 'satisfaccion';

    #[Url]
    public int $dias = 90;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('crm.ver');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = EncuestaNps::where('calificacion', '<=', 6)->whereNull('seguimiento_at')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * @return array{nps: ?int, respuestas: int, enviadas: int, promotores: int, pasivos: int, detractores: int, tasa: ?int}
     */
    public function getResumen(): array
    {
        $enviadas = EncuestaNps::where('enviada_at', '>=', now()->subDays($this->dias))->get(['calificacion']);
        $cal = $enviadas->pluck('calificacion')->filter(fn ($v) => $v !== null);

        return [
            'nps' => EncuestaNps::calcular($cal),
            'respuestas' => $cal->count(),
            'enviadas' => $enviadas->count(),
            'promotores' => $cal->filter(fn ($v) => $v >= 9)->count(),
            'pasivos' => $cal->filter(fn ($v) => $v >= 7 && $v <= 8)->count(),
            'detractores' => $cal->filter(fn ($v) => $v <= 6)->count(),
            'tasa' => $enviadas->count() ? (int) round($cal->count() / $enviadas->count() * 100) : null,
        ];
    }

    /**
     * @return Collection<int, array{nombre: string, nps: ?int, n: int}>
     */
    public function getPor(string $dimension): Collection
    {
        return EncuestaNps::with(['orden.asesor', 'orden.aseguradora'])
            ->whereNotNull('calificacion')->where('respondida_at', '>=', now()->subDays($this->dias))->get()
            ->groupBy(fn (EncuestaNps $e) => $dimension === 'asesor'
                ? ($e->orden->asesor?->name ?? 'Sin asesor')
                : ($e->orden->aseguradora?->nombre ?? 'Particular / flotilla'))
            ->map(fn ($grupo, $nombre) => ['nombre' => $nombre, 'nps' => EncuestaNps::calcular($grupo->pluck('calificacion')), 'n' => $grupo->count()])
            ->sortByDesc('n')->values();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(EncuestaNps::query()->whereNotNull('respondida_at')->with(['cliente', 'orden', 'responsableSeguimiento']))
            ->columns([
                TextColumn::make('respondida_at')->label('Fecha')->dateTime('d/m/y')->sortable(),
                TextColumn::make('calificacion')->label('Calificación')->badge()
                    ->color(fn ($state) => $state >= 9 ? 'success' : ($state >= 7 ? 'warning' : 'danger')),
                TextColumn::make('cliente.nombre_completo')->label('Cliente')->description(fn (EncuestaNps $r) => $r->cliente?->telefono),
                TextColumn::make('orden.folio')->label('Orden')
                    ->url(fn (EncuestaNps $r) => OrdenServicioResource::getUrl('view', ['record' => $r->orden_servicio_id])),
                TextColumn::make('comentario')->label('Comentario')->wrap()->limit(160)->placeholder('—'),
                TextColumn::make('seguimiento_notas')->label('Seguimiento')->wrap()->limit(120)
                    ->placeholder(fn (EncuestaNps $r) => $r->grupo() === 'detractor' ? 'Pendiente' : '—')
                    ->description(fn (EncuestaNps $r) => $r->seguimiento_at ? $r->responsableSeguimiento?->name.' · '.$r->seguimiento_at->format('d/m') : null),
            ])
            ->filters([
                SelectFilter::make('grupo')->label('Grupo')
                    ->options(['promotor' => 'Promotores (9-10)', 'pasivo' => 'Pasivos (7-8)', 'detractor' => 'Detractores (0-6)'])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'promotor' => $query->where('calificacion', '>=', 9),
                        'pasivo' => $query->whereBetween('calificacion', [7, 8]),
                        'detractor' => $query->where('calificacion', '<=', 6),
                        default => $query,
                    }),
                TernaryFilter::make('seguimiento_at')->label('Con seguimiento')->nullable(),
            ])
            ->defaultSort('respondida_at', 'desc')
            ->recordActions([
                Action::make('seguimiento')->label('Seguimiento')->icon(Heroicon::OutlinedPhone)
                    ->visible(fn (EncuestaNps $r) => $r->calificacion <= 8 && auth()->user()->can('crm.gestionar'))
                    ->fillForm(fn (EncuestaNps $r) => ['seguimiento_notas' => $r->seguimiento_notas])
                    ->schema([Textarea::make('seguimiento_notas')->label('¿Qué se habló con el cliente y qué se acordó?')->required()->rows(4)])
                    ->action(fn (EncuestaNps $r, array $data) => $r->update([
                        'seguimiento_notas' => $data['seguimiento_notas'],
                        'seguimiento_user_id' => auth()->id(),
                        'seguimiento_at' => now(),
                    ])),
            ]);
    }
}
