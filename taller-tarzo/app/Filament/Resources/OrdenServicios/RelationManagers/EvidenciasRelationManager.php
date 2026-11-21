<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Actions\Piso\RegistrarEvidencia;
use App\Enums\MomentoEvidencia;
use App\Models\Evidencia;
use App\Support\Media;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EvidenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'evidencias';

    protected static ?string $title = 'Evidencias';

    protected static ?string $modelLabel = 'evidencia';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) (auth()->user()?->can('piso.ver') || auth()->user()?->can('recepcion.ver'));
    }

    private function puedeSubir(): bool
    {
        $u = auth()->user();

        return (bool) ($u?->can('piso.gestionar') || $u?->can('piso.tareas_propias') || $u?->can('recepcion.gestionar'));
    }

    protected function canDelete(Model $record): bool
    {
        return (bool) auth()->user()?->can('piso.gestionar');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['etapa', 'autor']))
            ->columns([
                ImageColumn::make('archivo')->label('Foto')->disk(fn () => Media::nombreDisco())->visibility('private')->imageHeight(72),
                TextColumn::make('momento')->label('Momento')->badge()->description(fn (Evidencia $r) => $r->descripcion),
                TextColumn::make('etapa.nombre')->label('Etapa')->placeholder('—'),
                TextColumn::make('autor.name')->label('Subió')->placeholder('—')->description(fn (Evidencia $r) => $r->created_at->format('d/m H:i')),
                ToggleColumn::make('visible_cliente')->label('La ve el cliente')
                    ->disabled(fn () => ! auth()->user()?->can('recepcion.gestionar')),
            ])
            ->filters([
                SelectFilter::make('etapa_id')->label('Etapa')->relationship('etapa', 'nombre'),
                SelectFilter::make('momento')->label('Momento')->options(MomentoEvidencia::class),
            ])
            ->headerActions([
                Action::make('subir')->label('Subir fotos')->icon('heroicon-o-camera')
                    ->visible(fn () => $this->puedeSubir())
                    ->schema(fn (Schema $schema) => $schema->components([
                        ToggleButtons::make('momento')->label('¿Qué muestran?')->options(MomentoEvidencia::class)->inline()->required()
                            ->default(MomentoEvidencia::Proceso->value),
                        FileUpload::make('archivos')->label('Fotos')->multiple()->image()->required()->maxFiles(12)
                            ->disk(fn () => Media::nombreDisco())->visibility('private')
                            ->directory(fn () => 'evidencias/'.$this->getOwnerRecord()->folio)
                            ->automaticallyResizeImagesToWidth((string) config('tarzo.media.max_foto_px')),
                        TextInput::make('descripcion')->label('Pieza o nota')->maxLength(255),
                        Toggle::make('visible_cliente')->label('Mostrar al cliente'),
                    ]))
                    ->action(function (array $data) {
                        app(RegistrarEvidencia::class)($this->getOwnerRecord(), array_values($data['archivos']), $data['momento'] instanceof MomentoEvidencia ? $data['momento'] : MomentoEvidencia::from($data['momento']),
                            auth()->user(), null, $data['descripcion'] ?: null, (bool) ($data['visible_cliente'] ?? false));
                        Notification::make()->title('Fotos guardadas. Se marcan con folio y fecha en unos momentos.')->success()->send();
                    }),
                Action::make('album')->label('Álbum PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                    ->url(fn () => route('ordenes.album', $this->getOwnerRecord()), shouldOpenInNewTab: true),
                Action::make('albumCliente')->label('Álbum para el cliente')->icon('heroicon-o-user')->color('gray')
                    ->url(fn () => route('ordenes.album', [$this->getOwnerRecord(), 'cliente' => 1]), shouldOpenInNewTab: true),
            ])
            ->recordActions([DeleteAction::make()])
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin evidencias')
            ->emptyStateDescription('Fotos de piezas dañadas, desmontadas, nuevas instaladas y terminado.');
    }
}
