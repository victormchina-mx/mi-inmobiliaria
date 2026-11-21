<?php

namespace App\Filament\Pages;

use App\Enums\EstadoTarea;
use App\Enums\TipoBahia;
use App\Models\Tarea;
use App\Models\Tecnico;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Carga de cada técnico contra su capacidad del día y agenda de la cabina de pintura.
 */
class CargaTrabajo extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.carga-trabajo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Piso';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Carga de trabajo';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('piso.gestionar');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Tecnico::query()->where('activo', true)->with(['area', 'tareas' => fn ($q) => $q->where('estado', EstadoTarea::EnProceso->value)->with('orden')]))
            ->columns([
                TextColumn::make('nombre')->label('Técnico')->searchable()->description(fn (Tecnico $r) => $r->area?->nombre),
                TextColumn::make('pendientes')->label('Tareas abiertas')
                    ->state(fn (Tecnico $r) => $r->tareas()->abiertas()->count()),
                TextColumn::make('horas')->label('Horas asignadas')
                    ->state(fn (Tecnico $r) => $r->horasPendientes())->numeric(1)
                    ->description(fn (Tecnico $r) => 'Capacidad '.rtrim(rtrim(number_format((float) $r->capacidad_horas_dia, 1), '0'), '.').' h/día'),
                TextColumn::make('carga')->label('Carga')->badge()
                    ->state(fn (Tecnico $r) => $r->capacidad_horas_dia > 0 ? (int) round($r->horasPendientes() / $r->capacidad_horas_dia * 100) : null)
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state.' %')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state > 150 => 'danger',
                        $state > 100 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('ahora')->label('Trabajando en')->placeholder('Libre')
                    ->state(fn (Tecnico $r) => ($t = $r->tareas->first()) ? "{$t->orden->folio} · {$t->descripcion}" : null)->wrap(),
                TextColumn::make('eficiencia')->label('Eficiencia (30 días)')->badge()
                    ->state(fn (Tecnico $r) => self::eficiencia($r))
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state.' %')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 100 ? 'success' : ($state >= 80 ? 'warning' : 'danger')))
                    ->tooltip('Horas estándar ÷ horas reales de tareas terminadas. Más de 100 % = más rápido que el estándar.'),
            ])
            ->filters([SelectFilter::make('area_id')->label('Área')->relationship('area', 'nombre')])
            ->paginated(false);
    }

    public static function eficiencia(Tecnico $tecnico): ?int
    {
        $terminadas = $tecnico->tareas()->where('estado', EstadoTarea::Terminada->value)
            ->where('terminada_at', '>=', now()->subDays(30))->where('horas_reales', '>', 0);

        $reales = (float) $terminadas->clone()->sum('horas_reales');

        return $reales > 0 ? (int) round((float) $terminadas->clone()->sum('horas_estandar') / $reales * 100) : null;
    }

    /**
     * Tareas en cabinas de pintura para los próximos 7 días (las sin fecha van en "Sin programar").
     *
     * @return Collection<string, Collection<int, Tarea>>
     */
    public function getAgendaCabina(): Collection
    {
        return Tarea::query()
            ->abiertas()
            ->whereHas('bahia', fn (Builder $q) => $q->where('tipo', TipoBahia::CabinaPintura->value))
            ->where(fn (Builder $q) => $q->whereNull('programada_para')->orWhereBetween('programada_para', [today(), today()->addDays(6)]))
            ->with(['orden.vehiculo', 'bahia', 'tecnico'])
            ->orderBy('programada_para')
            ->get()
            ->groupBy(fn (Tarea $t) => $t->programada_para?->translatedFormat('l j M') ?? 'Sin programar');
    }
}
