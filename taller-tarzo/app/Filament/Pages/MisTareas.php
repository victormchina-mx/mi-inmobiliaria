<?php

namespace App\Filament\Pages;

use App\Actions\Piso\RegistrarEvidencia;
use App\Actions\Piso\RelojTarea;
use App\Enums\EstadoTarea;
use App\Enums\MomentoEvidencia;
use App\Enums\MotivoPausa;
use App\Models\Tarea;
use App\Models\Tecnico;
use App\Support\Media;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Pantalla del técnico (pensada para el celular): sus tareas, reloj y evidencias.
 */
class MisTareas extends Page
{
    protected string $view = 'filament.pages.mis-tareas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    protected static string|UnitEnum|null $navigationGroup = 'Piso';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Mis tareas';

    public static function canAccess(): bool
    {
        $u = auth()->user();

        return (bool) ($u?->can('piso.tareas_propias') && $u->tecnico);
    }

    public function tecnico(): Tecnico
    {
        return auth()->user()->tecnico;
    }

    /**
     * @return Collection<int, Tarea>
     */
    public function getMias(): Collection
    {
        return $this->tecnico()->tareas()->abiertas()
            ->with(['orden.vehiculo', 'orden.etapa', 'bahia', 'registroAbierto'])
            ->get()
            ->sortBy(fn (Tarea $t) => [$t->estado === EstadoTarea::EnProceso ? 0 : 1, $t->programada_para?->timestamp ?? PHP_INT_MAX, $t->id])
            ->values();
    }

    /**
     * Tareas sin técnico de mi área que puedo tomar.
     *
     * @return Collection<int, Tarea>
     */
    public function getDisponibles(): Collection
    {
        $area = $this->tecnico()->area_id;

        if (! $area) {
            return collect();
        }

        return Tarea::abiertas()->whereNull('tecnico_id')->where('area_id', $area)
            ->with(['orden.vehiculo'])->orderBy('id')->limit(10)->get();
    }

    /**
     * @return Collection<int, Tarea>
     */
    public function getTerminadasHoy(): Collection
    {
        return $this->tecnico()->tareas()->where('estado', EstadoTarea::Terminada->value)
            ->whereDate('terminada_at', today())->with('orden')->get();
    }

    private function tareaPropia(int $id): Tarea
    {
        $tarea = Tarea::findOrFail($id);
        abort_unless($tarea->tecnico_id === null || $tarea->tecnico_id === $this->tecnico()->id, 403);

        return $tarea;
    }

    public function iniciar(int $tareaId): void
    {
        $this->ejecutar(fn () => app(RelojTarea::class)->iniciar($this->tareaPropia($tareaId), $this->tecnico()), 'Tarea iniciada');
    }

    public function terminar(int $tareaId): void
    {
        $this->ejecutar(fn () => app(RelojTarea::class)->terminar($this->tareaPropia($tareaId)), 'Tarea terminada');
    }

    public function pausarAction(): Action
    {
        return Action::make('pausar')->label('Pausar')->modalHeading('¿Por qué pausas?')->modalWidth('md')
            ->schema(fn (Schema $schema) => $schema->components([
                ToggleButtons::make('motivo')->hiddenLabel()->options(MotivoPausa::class)->required()
                    ->default(MotivoPausa::Comida->value),
            ]))
            ->action(fn (array $data, array $arguments) => $this->ejecutar(
                fn () => app(RelojTarea::class)->pausar($this->tareaPropia($arguments['tarea']), $data['motivo'] instanceof MotivoPausa ? $data['motivo'] : MotivoPausa::from($data['motivo'])),
                'Tarea en pausa'
            ));
    }

    public function evidenciaAction(): Action
    {
        return Action::make('evidencia')->label('Fotos')->modalHeading('Subir fotos de evidencia')
            ->schema(fn (Schema $schema, array $arguments) => $schema->components([
                ToggleButtons::make('momento')->label('¿Qué muestran?')->options(MomentoEvidencia::class)->required()->inline()
                    ->default(MomentoEvidencia::Proceso->value),
                FileUpload::make('archivos')->label('Fotos')->multiple()->image()->required()->maxFiles(10)
                    ->disk(fn () => Media::nombreDisco())->visibility('private')
                    ->directory(fn () => 'evidencias/'.Tarea::find($arguments['tarea'] ?? 0)?->orden?->folio)
                    ->automaticallyResizeImagesToWidth((string) config('tarzo.media.max_foto_px')),
                TextInput::make('descripcion')->label('Nota (opcional)')->maxLength(255),
            ]))
            ->action(function (array $data, array $arguments) {
                $tarea = $this->tareaPropia($arguments['tarea']);
                app(RegistrarEvidencia::class)($tarea->orden, array_values($data['archivos']), $data['momento'] instanceof MomentoEvidencia ? $data['momento'] : MomentoEvidencia::from($data['momento']), auth()->user(), $tarea, $data['descripcion'] ?: null);
                Notification::make()->title(count($data['archivos']).' foto(s) guardadas')->success()->send();
            });
    }

    private function ejecutar(callable $accion, string $mensaje): void
    {
        try {
            $accion();
            Notification::make()->title($mensaje)->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->warning()->send();
        }
    }
}
