<?php

namespace App\Filament\Resources\Flotillas\RelationManagers;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\Cliente;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClientesRelationManager extends RelationManager
{
    protected static string $relationship = 'clientes';

    protected static ?string $title = 'Clientes de la flotilla';

    protected static ?string $modelLabel = 'cliente';

    /** Editable también desde la página de consulta (Filament las deja en solo lectura por defecto). */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    protected function canAssociate(): bool
    {
        return (bool) auth()->user()?->can('recepcion.gestionar');
    }

    protected function canDissociate(Model $record): bool
    {
        return $this->canAssociate();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->modifyQueryUsing(fn ($query) => $query->withCount('vehiculos'))
            ->columns([
                TextColumn::make('nombre')->label('Cliente')->formatStateUsing(fn (Cliente $record) => $record->nombre_completo)->searchable(['nombre', 'apellidos']),
                TextColumn::make('telefono')->label('Teléfono')->placeholder('—'),
                TextColumn::make('vehiculos_count')->label('Vehículos'),
            ])
            ->recordUrl(fn (Cliente $record) => ClienteResource::getUrl('view', ['record' => $record]))
            ->headerActions([AssociateAction::make()->label('Agregar cliente existente')->preloadRecordSelect()])
            ->recordActions([DissociateAction::make()->label('Quitar')]);
    }
}
