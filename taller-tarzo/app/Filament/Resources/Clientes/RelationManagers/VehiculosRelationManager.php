<?php

namespace App\Filament\Resources\Clientes\RelationManagers;

use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Filament\Resources\Vehiculos\Schemas\VehiculoForm;
use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Models\Vehiculo;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VehiculosRelationManager extends RelationManager
{
    protected static string $relationship = 'vehiculos';

    protected static ?string $title = 'Vehículos';

    protected static ?string $modelLabel = 'vehículo';

    /** Editable también desde la página de consulta (Filament las deja en solo lectura por defecto). */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('recepcion.gestionar');
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components(VehiculoForm::campos());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->modifyQueryUsing(fn ($query) => $query->withCount('ordenes'))
            ->columns([
                TextColumn::make('placas')->label('Placas')->placeholder('—')->weight('bold'),
                TextColumn::make('marca')->label('Vehículo')->formatStateUsing(fn (Vehiculo $record) => "{$record->marca} {$record->modelo} {$record->anio}"),
                TextColumn::make('vin')->label('VIN')->placeholder('—')->fontFamily('mono'),
                TextColumn::make('kilometraje')->label('Km')->numeric(),
                TextColumn::make('ordenes_count')->label('Visitas'),
            ])
            ->recordUrl(fn (Vehiculo $record) => VehiculoResource::getUrl('view', ['record' => $record]))
            ->headerActions([CreateAction::make()->label('Agregar vehículo')])
            ->recordActions([
                Action::make('recibir')->label('Recibir')->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn () => $this->canCreate())
                    ->url(fn (Vehiculo $record) => OrdenServicioResource::getUrl('create', ['vehiculo' => $record->id])),
                EditAction::make(),
            ]);
    }
}
