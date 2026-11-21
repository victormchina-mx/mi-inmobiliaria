<?php

namespace App\Filament\Pages;

use App\Actions\Ordenes\CambiarEtapa;
use App\Enums\TipoOrden;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Aseguradora;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Models\Tecnico;
use App\Models\User;
use App\Support\DatosTablero;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Tablero Kanban del taller: dónde está cada auto y cuánto lleva en cada etapa.
 */
class Tablero extends Page
{
    protected string $view = 'filament.pages.tablero';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Piso';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Tablero del taller';

    protected static ?string $navigationLabel = 'Tablero';

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?string $asesor = null;

    public ?string $aseguradora = null;

    public ?string $tipo = null;

    public ?string $tecnico = null;

    public string $buscar = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('piso.ver');
    }

    public function puedeMover(): bool
    {
        return (bool) auth()->user()?->can('piso.gestionar');
    }

    public function intervaloRefresco(): string
    {
        // En VPS con Reverb el refresco es solo un respaldo.
        return config('tarzo.tiempo_real.modo') === 'reverb' ? '60s' : (string) config('tarzo.tiempo_real.intervalo_polling', '15s');
    }

    /**
     * @return Collection<int, array{etapa: Etapa, ordenes: Collection<int, OrdenServicio>, excedido: bool}>
     */
    public function getColumnas(): Collection
    {
        return DatosTablero::columnas([
            'asesor_id' => $this->asesor,
            'aseguradora_id' => $this->aseguradora,
            'tipo' => $this->tipo,
            'tecnico_id' => $this->tecnico,
            'buscar' => $this->buscar,
        ]);
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    public function getOpcionesFiltro(): array
    {
        return [
            'asesores' => User::role(['asesor', 'dueno'])->where('activo', true)->orderBy('name')->pluck('name', 'id')->all(),
            'aseguradoras' => Aseguradora::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all(),
            'tipos' => collect(TipoOrden::cases())->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])->all(),
            'tecnicos' => Tecnico::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all(),
        ];
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['asesor', 'aseguradora', 'tipo', 'tecnico', 'buscar']);
    }

    /**
     * Llamado al soltar una tarjeta en otra columna.
     */
    public function moverOrden(int $ordenId, int $etapaId): void
    {
        $orden = OrdenServicio::findOrFail($ordenId);
        $destino = Etapa::findOrFail($etapaId);

        try {
            (new CambiarEtapa)($orden, $destino, auth()->user());
            Notification::make()->title("{$orden->folio} → {$destino->nombre}")->success()->send();
        } catch (ValidationException $e) {
            $notificacion = Notification::make()->title("No se puede mover {$orden->folio} a {$destino->nombre}")
                ->body(collect($e->errors())->flatten()->implode(' '))
                ->warning()->persistent();

            if (auth()->user()?->can('piso.forzar_etapa')) {
                $notificacion->actions([
                    Action::make('forzar')->label('Mover de todos modos…')->button()
                        ->dispatch('tablero-forzar', ['orden' => $orden->id, 'etapa' => $destino->id]),
                ]);
            }

            $notificacion->send();
        } catch (AuthorizationException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function getListeners(): array
    {
        return ['tablero-forzar' => 'abrirForzar'];
    }

    public function abrirForzar(int $orden, int $etapa): void
    {
        $this->mountAction('mover', ['orden' => $orden, 'etapa' => $etapa, 'forzar' => true]);
    }

    /**
     * Mover desde el menú de la tarjeta (tabletas y celulares no permiten arrastrar).
     */
    public function moverAction(): Action
    {
        return Action::make('mover')
            ->modalHeading(fn (array $arguments) => 'Mover '.OrdenServicio::find($arguments['orden'] ?? 0)?->folio)
            ->visible(fn () => $this->puedeMover())
            ->fillForm(fn (array $arguments) => ['etapa_id' => $arguments['etapa'] ?? null, 'forzar' => (bool) ($arguments['forzar'] ?? false)])
            ->schema(fn (Schema $schema, array $arguments) => $schema->components([
                Select::make('etapa_id')->label('Nueva etapa')->required()
                    ->options(Etapa::ordenadas()->where('es_final', false)->pluck('nombre', 'id')),
                Toggle::make('forzar')->label('Saltar las reglas de la etapa')->live()
                    ->visible(fn () => auth()->user()?->can('piso.forzar_etapa')),
                Text::make(function (Get $get) use ($arguments) {
                    $orden = OrdenServicio::find($arguments['orden'] ?? 0);
                    $etapa = Etapa::find($get('etapa_id'));

                    return $orden && $etapa ? implode(' ', CambiarEtapa::problemas($orden, $etapa)) : '';
                })->color('warning'),
                Textarea::make('comentario')->label('Comentario')->rows(2)->maxLength(255)
                    ->required(fn (Get $get) => (bool) $get('forzar'))
                    ->helperText(fn (Get $get) => $get('forzar') ? 'Obligatorio: queda registrado como excepción.' : null),
            ]))
            ->action(function (array $data, array $arguments) {
                $orden = OrdenServicio::findOrFail($arguments['orden']);
                $destino = Etapa::findOrFail($data['etapa_id']);

                try {
                    (new CambiarEtapa)($orden, $destino, auth()->user(), $data['comentario'] ?? null, (bool) ($data['forzar'] ?? false));
                    Notification::make()->title("{$orden->folio} → {$destino->nombre}")->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()->title('No se movió')->body(collect($e->errors())->flatten()->implode(' '))->warning()->send();
                }
            });
    }

    public function urlOrden(OrdenServicio $orden): ?string
    {
        return auth()->user()?->can('recepcion.ver') ? OrdenServicioResource::getUrl('view', ['record' => $orden]) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tv')->label('Modo TV')->icon(Heroicon::OutlinedTv)->color('gray')
                ->url(route('tablero.tv'), shouldOpenInNewTab: true),
        ];
    }
}
