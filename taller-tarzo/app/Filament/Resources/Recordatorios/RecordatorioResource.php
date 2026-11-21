<?php

namespace App\Filament\Resources\Recordatorios;

use App\Actions\Postventa\EnviarRecordatorios;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Recordatorios\Pages\ManageRecordatorios;
use App\Models\Recordatorio;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RecordatorioResource extends Resource
{
    use ControlaPermisos;

    public const ESTADOS = ['programado' => 'Programado', 'enviado' => 'Enviado', 'atendido' => 'Atendido', 'cancelado' => 'Cancelado'];

    protected static string $modulo = 'crm';

    protected static ?string $model = Recordatorio::class;

    protected static ?string $slug = 'recordatorios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 3;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'recordatorio';

    protected static ?string $pluralModelLabel = 'Recordatorios';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $gestiona = fn () => auth()->user()->can('crm.gestionar');

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['vehiculo', 'cliente']))
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('cliente.nombre_completo')->label('Cliente')->searchable(['nombre', 'apellidos'])
                    ->description(fn (Recordatorio $r) => $r->cliente?->telefono),
                TextColumn::make('vehiculo.descripcion')->label('Vehículo'),
                TextColumn::make('titulo')->label('Recordatorio')->wrap()
                    ->description(fn (Recordatorio $r) => $r->km_estimado ? '≈ '.number_format($r->km_estimado).' km' : null),
                TextColumn::make('tipo')->label('Tipo')->badge()->formatStateUsing(fn ($state) => $state === 'verificacion' ? 'Verificación' : 'Servicio')
                    ->color(fn ($state) => $state === 'verificacion' ? 'info' : 'primary'),
                TextColumn::make('estado')->label('Estado')->badge()->formatStateUsing(fn ($state) => self::ESTADOS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'programado' => 'warning', 'enviado' => 'info', 'atendido' => 'success', default => 'gray'
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')->options(self::ESTADOS),
                SelectFilter::make('tipo')->options(['servicio' => 'Servicio', 'verificacion' => 'Verificación']),
            ])
            ->defaultSort('fecha')
            ->recordActions([
                Action::make('enviar')->label('Enviar ahora')->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (Recordatorio $r) => $r->estado === 'programado' && $gestiona())
                    ->requiresConfirmation()
                    ->action(function (Recordatorio $r) {
                        $ok = app(EnviarRecordatorios::class)->enviar($r);
                        $ok ? Notification::make()->title('Recordatorio en cola de envío')->success()->send()
                            : Notification::make()->title('No se envió')->body($r->mensaje?->error)->warning()->send();
                    }),
                Action::make('atendido')->label('Agendó')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Recordatorio $r) => in_array($r->estado, ['programado', 'enviado'], true) && $gestiona())
                    ->action(fn (Recordatorio $r) => $r->update(['estado' => 'atendido'])),
                Action::make('cancelar')->label('Cancelar')->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->visible(fn (Recordatorio $r) => $r->estado === 'programado' && $gestiona())
                    ->action(fn (Recordatorio $r) => $r->update(['estado' => 'cancelado'])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRecordatorios::route('/')];
    }
}
