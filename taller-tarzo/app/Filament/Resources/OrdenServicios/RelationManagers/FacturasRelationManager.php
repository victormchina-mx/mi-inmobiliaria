<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Filament\Resources\Facturas\FacturaResource;
use App\Models\Factura;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FacturasRelationManager extends RelationManager
{
    protected static string $relationship = 'facturas';

    protected static ?string $title = 'Facturación';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('facturacion.ver');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('folio')->label('Folio')->formatStateUsing(fn (Factura $r) => $r->folio_completo)->description(fn (Factura $r) => FacturaResource::TIPOS[$r->tipo]),
                TextColumn::make('receptor_nombre')->label('Receptor'),
                TextColumn::make('metodo_pago')->label('Método')->placeholder('—'),
                TextColumn::make('total')->label('Total')->money('MXN'),
                TextColumn::make('saldo')->label('Saldo')->money('MXN')->state(fn (Factura $r) => $r->tipo === 'I' ? $r->saldo : null)->placeholder('—'),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->defaultSort('id')
            ->recordActions([Action::make('ver')->label('Abrir')->url(fn (Factura $r) => FacturaResource::getUrl('view', ['record' => $r]))]);
    }
}
