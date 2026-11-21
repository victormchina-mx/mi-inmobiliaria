<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Actions\Piso\GenerarTareas;
use App\Enums\EstadoTarea;
use App\Models\Bahia;
use App\Models\Tarea;
use App\Models\Tecnico;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TareasRelationManager extends RelationManager
{
    protected static string $relationship = 'tareas';

    protected static ?string $title = 'Tareas de taller';

    protected static ?string $modelLabel = 'tarea';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('piso.ver');
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('piso.gestionar');
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canCreate() && $record->estado === EstadoTarea::Pendiente && $record->registros()->doesntExist();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('descripcion')->label('Tarea')->required()->maxLength(255)->columnSpanFull(),
            Select::make('area_id')->label('Área')->relationship('area', 'nombre')->preload()->live(),
            TextInput::make('horas_estandar')->label('Horas estándar')->numeric()->minValue(0)->step(0.1)->required(),
            Select::make('tecnico_id')->label('Técnico')
                ->options(fn () => Tecnico::where('activo', true)->orderBy('nombre')->get()
                    ->mapWithKeys(fn (Tecnico $t) => [$t->id => $t->nombre.' · '.number_format($t->horasPendientes(), 1).' h asignadas']))
                ->searchable(),
            Select::make('bahia_id')->label('Bahía / cabina')
                ->options(fn () => Bahia::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')),
            DatePicker::make('programada_para')->label('Programada para')->native(false)->displayFormat('d/m/Y'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->modifyQueryUsing(fn ($query) => $query->with(['area', 'tecnico', 'bahia', 'registroAbierto']))
            ->columns([
                TextColumn::make('descripcion')->label('Tarea')->wrap()->description(fn (Tarea $r) => $r->area?->nombre),
                TextColumn::make('tecnico.nombre')->label('Técnico')->placeholder('Sin asignar'),
                TextColumn::make('bahia.nombre')->label('Bahía')->placeholder('—')
                    ->description(fn (Tarea $r) => $r->programada_para?->format('d/m')),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('horas_estandar')->label('Estándar')->suffix(' h'),
                TextColumn::make('horas_reales')->label('Real')
                    ->state(fn (Tarea $r) => $r->horasAcumuladas())->suffix(' h')
                    ->color(fn (Tarea $r) => $r->horasAcumuladas() > $r->horas_estandar && $r->horas_estandar > 0 ? 'danger' : null),
                TextColumn::make('eficiencia')->label('Eficiencia')
                    ->state(fn (Tarea $r) => $r->eficiencia() !== null ? (int) round($r->eficiencia() * 100).' %' : '—'),
            ])
            ->headerActions([
                Action::make('generar')->label('Generar desde cotización')->icon('heroicon-o-arrow-path')
                    ->visible(fn () => $this->canCreate())
                    ->action(function () {
                        $cotizacion = $this->getOwnerRecord()->cotizacionActual;
                        $n = $cotizacion ? app(GenerarTareas::class)($cotizacion) : 0;
                        Notification::make()->title($n ? "{$n} tareas nuevas" : 'No hay partidas autorizadas nuevas')->send();
                    }),
                CreateAction::make()->label('Agregar tarea'),
            ])
            ->recordActions([EditAction::make()->label('Asignar'), DeleteAction::make()])
            ->emptyStateHeading('Sin tareas')
            ->emptyStateDescription('Se generan solas al autorizarse la cotización, o agrégalas a mano.');
    }
}
