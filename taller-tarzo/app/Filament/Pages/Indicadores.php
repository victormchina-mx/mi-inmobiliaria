<?php

namespace App\Filament\Pages;

use App\Enums\TipoOrden;
use App\Models\Aseguradora;
use App\Models\User;
use App\Services\Finanzas\Indicadores as Calculo;
use App\Support\ExportarIndicadores;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Tablero mensual de KPIs con definiciones, días por etapa (cuello de botella), técnicos y tendencia.
 */
class Indicadores extends Page
{
    protected string $view = 'filament.pages.indicadores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Indicadores del taller';

    protected static ?string $slug = 'indicadores';

    #[Url]
    public string $mes = '';

    #[Url]
    public ?string $tipo = null;

    #[Url]
    public ?string $aseguradora = null;

    #[Url]
    public ?string $asesor = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('reportes.ver');
    }

    public function mount(): void
    {
        $this->mes = $this->mes ?: now()->format('Y-m');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodo(): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', ($this->mes ?: now()->format('Y-m')).'-01')->startOfMonth();

        return [$inicio, $inicio->copy()->endOfMonth()];
    }

    public function filtros(): array
    {
        return ['tipo' => $this->tipo, 'aseguradora_id' => $this->aseguradora ? (int) $this->aseguradora : null, 'asesor_id' => $this->asesor ? (int) $this->asesor : null];
    }

    public function getDatos(): array
    {
        [$desde, $hasta] = $this->periodo();

        return app(Calculo::class)->calcular($desde, $hasta, $this->filtros());
    }

    public function getTendencia(): array
    {
        return app(Calculo::class)->tendencia(6, $this->filtros());
    }

    public function opciones(): array
    {
        return [
            'meses' => collect(range(0, 17))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonthsNoOverflow($i)->format('Y-m') => ucfirst(now()->startOfMonth()->subMonthsNoOverflow($i)->translatedFormat('F Y'))])->all(),
            'tipos' => collect(TipoOrden::cases())->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])->all(),
            'aseguradoras' => Aseguradora::orderBy('nombre')->pluck('nombre', 'id')->all(),
            'asesores' => User::role(['asesor', 'dueno'])->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excel')->label('Exportar a Excel')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(function () {
                    [$desde, $hasta] = $this->periodo();

                    return response()->streamDownload(
                        fn () => print (ExportarIndicadores::xlsx($desde, $hasta, $this->filtros())),
                        'indicadores-'.$desde->format('Y-m').'.xlsx',
                        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    );
                }),
        ];
    }
}
