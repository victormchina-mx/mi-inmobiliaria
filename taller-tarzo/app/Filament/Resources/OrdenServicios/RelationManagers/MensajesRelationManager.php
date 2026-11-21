<?php

namespace App\Filament\Resources\OrdenServicios\RelationManagers;

use App\Models\Mensaje;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Lo que se le ha comunicado al cliente sobre esta orden (automático y manual), con su estado de entrega.
 */
class MensajesRelationManager extends RelationManager
{
    protected static string $relationship = 'mensajes';

    protected static ?string $title = 'Comunicación';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('crm.ver');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/y H:i'),
                TextColumn::make('direccion')->label('')->formatStateUsing(fn ($state) => $state === 'entrante' ? 'Cliente' : 'Taller'),
                TextColumn::make('evento')->label('Evento')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('canal')->label('Canal')->formatStateUsing(fn ($state) => match ($state) {
                    'whatsapp' => 'WhatsApp', 'correo' => 'Correo', default => '—',
                }),
                TextColumn::make('cuerpo')->label('Texto')->wrap()->limit(140)->placeholder(fn (Mensaje $r) => $r->error),
                TextColumn::make('estado')->label('Estado')->badge()->tooltip(fn (Mensaje $r) => $r->error),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
