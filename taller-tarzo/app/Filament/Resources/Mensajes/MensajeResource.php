<?php

namespace App\Filament\Resources\Mensajes;

use App\Enums\EstadoMensaje;
use App\Enums\EventoMensaje;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Pages\Conversaciones;
use App\Filament\Resources\Mensajes\Pages\ManageMensajes;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Mensaje;
use App\Services\Comunicacion\Notificador;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Bitácora de todo lo que se envió (o no, y por qué) a los clientes.
 */
class MensajeResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'crm';

    protected static ?string $model = Mensaje::class;

    protected static ?string $slug = 'mensajes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 2;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'mensaje';

    protected static ?string $pluralModelLabel = 'Bitácora de mensajes';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Mensaje::where('estado', EstadoMensaje::Fallido->value)->where('created_at', '>=', now()->subDays(7))->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('cliente.nombre_completo')->label('Cliente'),
            TextEntry::make('destino')->label('Destino')->placeholder('—'),
            TextEntry::make('asunto')->label('Asunto')->placeholder('—')->columnSpanFull(),
            TextEntry::make('cuerpo')->label('Texto')->placeholder('—')->columnSpanFull()->prose(),
            TextEntry::make('error')->label('Motivo / error')->placeholder('—')->columnSpanFull()->color('danger'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['cliente', 'orden']))
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/y H:i')->sortable(),
                TextColumn::make('direccion')->label('')->formatStateUsing(fn ($state) => $state === 'entrante' ? '⬅' : '➡')
                    ->tooltip(fn ($state) => $state === 'entrante' ? 'Recibido' : 'Enviado'),
                TextColumn::make('cliente.nombre_completo')->label('Cliente')->searchable(['nombre', 'apellidos'])
                    ->url(fn (Mensaje $r) => $r->cliente_id ? Conversaciones::getUrl(['cliente' => $r->cliente_id]) : null),
                TextColumn::make('evento')->label('Evento')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('canal')->label('Canal')->formatStateUsing(fn ($state) => match ($state) {
                    'whatsapp' => 'WhatsApp', 'correo' => 'Correo', default => '—',
                }),
                TextColumn::make('cuerpo')->label('Texto')->limit(60)->placeholder(fn (Mensaje $r) => $r->error),
                TextColumn::make('orden.folio')->label('Orden')->placeholder('—')
                    ->url(fn (Mensaje $r) => $r->orden ? OrdenServicioResource::getUrl('view', ['record' => $r->orden]) : null),
                TextColumn::make('estado')->label('Estado')->badge()->tooltip(fn (Mensaje $r) => $r->error),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoMensaje::class),
                SelectFilter::make('evento')->options(EventoMensaje::class),
                SelectFilter::make('canal')->options(['whatsapp' => 'WhatsApp', 'correo' => 'Correo']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->label('Ver'),
                Action::make('reintentar')->label('Reintentar')->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Mensaje $r) => $r->estado === EstadoMensaje::Fallido && auth()->user()->can('crm.gestionar'))
                    ->requiresConfirmation()
                    ->action(function (Mensaje $r) {
                        Notificador::reintentar($r);
                        Notification::make()->title('Mensaje en cola de nuevo')->success()->send();
                    }),
            ])
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return ['index' => ManageMensajes::route('/')];
    }
}
