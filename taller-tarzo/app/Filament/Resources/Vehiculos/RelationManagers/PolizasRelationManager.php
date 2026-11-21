<?php

namespace App\Filament\Resources\Vehiculos\RelationManagers;

use App\Enums\Cobertura;
use App\Models\Poliza;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PolizasRelationManager extends RelationManager
{
    protected static string $relationship = 'polizas';

    protected static ?string $title = 'Pólizas de seguro';

    protected static ?string $modelLabel = 'póliza';

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
        return $this->canCreate();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('aseguradora_id')->label('Aseguradora')->relationship('aseguradora', 'nombre', fn ($query) => $query->where('activo', true))
                ->preload()->required(),
            TextInput::make('numero')->label('Número de póliza')->required()->maxLength(60),
            DatePicker::make('vigencia_inicio')->label('Vigencia desde')->native(false)->displayFormat('d/m/Y'),
            DatePicker::make('vigencia_fin')->label('Vigencia hasta')->native(false)->displayFormat('d/m/Y')->afterOrEqual('vigencia_inicio'),
            Select::make('cobertura')->label('Cobertura')->options(Cobertura::class),
            TextInput::make('deducible_porcentaje')->label('Deducible daños materiales')->numeric()->suffix('%')->minValue(0)->maxValue(100),
            TextInput::make('deducible_monto')->label('o deducible fijo')->numeric()->prefix('$')->minValue(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero')
            ->columns([
                TextColumn::make('aseguradora.nombre')->label('Aseguradora'),
                TextColumn::make('numero')->label('Póliza')->copyable(),
                TextColumn::make('cobertura')->label('Cobertura')->badge()->placeholder('—'),
                TextColumn::make('vigencia_fin')->label('Vigente hasta')->date('d/m/Y')->placeholder('—')
                    ->badge()->color(fn (Poliza $record) => $record->estaVigente() ? 'success' : 'danger')
                    ->description(fn (Poliza $record) => $record->estaVigente() ? null : 'Vencida'),
                TextColumn::make('deducible_porcentaje')->label('Deducible')
                    ->formatStateUsing(fn (Poliza $record) => $record->deducible_porcentaje !== null ? "{$record->deducible_porcentaje}%" : ($record->deducible_monto !== null ? '$'.number_format((float) $record->deducible_monto, 2) : '—')),
            ])
            ->defaultSort('vigencia_fin', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
