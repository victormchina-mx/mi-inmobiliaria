<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Actions\Cotizacion\AgregarPartidas;
use App\Enums\Prioridad;
use App\Filament\Resources\Cotizaciones\CotizacionResource;
use App\Models\Diagnostico;
use App\Models\Tecnico;
use App\Support\Media;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DiagnosticosRelationManager extends RelationManager
{
    protected static string $relationship = 'diagnosticos';

    protected static ?string $title = 'Diagnóstico';

    protected static ?string $modelLabel = 'hallazgo';

    protected static ?string $pluralModelLabel = 'hallazgos';

    /** Editable también desde la página de consulta (Filament las deja en solo lectura por defecto). */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    private function puedeRegistrar(): bool
    {
        $u = auth()->user();

        return (bool) ($u?->can('recepcion.gestionar') || $u?->can('piso.gestionar') || $u?->can('piso.tareas_propias'));
    }

    protected function canCreate(): bool
    {
        return $this->puedeRegistrar() && $this->getOwnerRecord()->cerrada_at === null;
    }

    protected function canEdit(Model $record): bool
    {
        return $this->puedeRegistrar() && ! $record->convertido_a_partida;
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canEdit($record);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('hallazgo')->label('Hallazgo')->placeholder('Balatas delanteras al 10%')->required()->maxLength(255)->columnSpanFull(),
            ToggleButtons::make('prioridad')->label('Prioridad')->options(Prioridad::class)->inline()
                ->default(Prioridad::Recomendado)->required(),
            Select::make('tecnico_id')->label('Técnico')
                ->options(fn () => Tecnico::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                ->default(fn () => auth()->user()?->tecnico?->id)
                ->searchable(),
            TextInput::make('codigos_dtc')->label('Códigos de falla (OBD-II)')->placeholder('P0301, P0420')
                ->helperText('Se normalizan automáticamente.')
                ->dehydrateStateUsing(fn (?string $state) => Diagnostico::normalizarDtc($state)),
            Textarea::make('detalle')->label('Detalle técnico')->rows(3)->columnSpanFull(),
            FileUpload::make('fotos')->label('Fotos')->multiple()->image()->maxFiles(8)->reorderable()
                ->disk(fn () => Media::nombreDisco())->visibility('private')
                ->directory(fn () => 'diagnosticos/'.$this->getOwnerRecord()->folio)
                ->automaticallyResizeImagesToWidth((string) config('tarzo.media.max_foto_px'))
                ->panelLayout('grid')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('hallazgo')
            ->columns([
                TextColumn::make('prioridad')->label('Prioridad')->badge()->sortable(),
                TextColumn::make('hallazgo')->label('Hallazgo')->wrap()->description(fn (Diagnostico $record) => $record->detalle),
                TextColumn::make('codigos_dtc')->label('DTC')->placeholder('—')->fontFamily('mono'),
                ImageColumn::make('fotos')->label('Fotos')->disk(fn () => Media::nombreDisco())->visibility('private')
                    ->stacked()->limit(3)->limitedRemainingText()->imageHeight(36),
                TextColumn::make('tecnico.nombre')->label('Técnico')->placeholder('—'),
                TextColumn::make('created_at')->label('Registrado')->since(),
                IconColumn::make('convertido_a_partida')->label('En cotización')->boolean(),
            ])
            ->filters([SelectFilter::make('prioridad')->label('Prioridad')->options(Prioridad::class)])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()->label('Agregar hallazgo')
                    ->mutateDataUsing(fn (array $data) => [...$data, 'user_id' => auth()->id()]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkAction::make('aCotizacion')->label('Pasar a la cotización')->icon('heroicon-o-calculator')
                    ->visible(fn () => auth()->user()?->can('cotizacion.gestionar'))
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $cotizacion = app(AbrirCotizacion::class)($this->getOwnerRecord(), auth()->user());

                        try {
                            app(AgregarPartidas::class)->desdeDiagnosticos($cotizacion, $records);
                        } catch (ValidationException $e) {
                            Notification::make()->title('No se agregaron')->body(collect($e->errors())->flatten()->first())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Hallazgos agregados a '.$cotizacion->folio)
                            ->body('Ajusta horas, refacciones y precios en la cotización.')->success()
                            ->actions([Action::make('abrir')->label('Abrir cotización')->url(CotizacionResource::getUrl('edit', ['record' => $cotizacion]))])
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Sin hallazgos todavía')
            ->emptyStateDescription('El técnico registra aquí lo que encuentra; el asesor lo convierte en presupuesto.');
    }
}
