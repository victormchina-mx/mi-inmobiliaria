<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Subcontratos y gastos directos (grúa, rectificadora, cristales…): restan a la utilidad de la orden.
 */
class GastosRelationManager extends RelationManager
{
    protected static string $relationship = 'gastos';

    protected static ?string $title = 'Subcontratos y gastos';

    protected static ?string $modelLabel = 'gasto';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('facturacion.ver');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('concepto')->label('Concepto')->required()->maxLength(160)->placeholder('Grúa, rectificado de cabeza, cristal…'),
            TextInput::make('proveedor')->label('Proveedor')->maxLength(160),
            TextInput::make('monto')->label('Monto sin IVA')->numeric()->prefix('$')->minValue(0.01)->required(),
            TextInput::make('factura_proveedor')->label('Folio de su factura')->maxLength(60),
            DatePicker::make('fecha')->label('Fecha')->default(today())->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        $gestiona = fn () => auth()->user()?->can('facturacion.gestionar');

        return $table
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('concepto')->label('Concepto')->description(fn ($r) => $r->proveedor),
                TextColumn::make('factura_proveedor')->label('Factura')->placeholder('—'),
                TextColumn::make('monto')->label('Monto')->money('MXN')->summarize(Sum::make()->money('MXN')),
            ])
            ->headerActions([CreateAction::make()->label('Agregar gasto')->visible($gestiona)->mutateDataUsing(fn (array $data) => [...$data, 'user_id' => auth()->id()])])
            ->recordActions([EditAction::make()->visible($gestiona), DeleteAction::make()->visible($gestiona)]);
    }
}
