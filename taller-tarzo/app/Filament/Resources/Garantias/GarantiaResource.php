<?php

namespace App\Filament\Resources\Garantias;

use App\Actions\Postventa\ReclamarGarantia;
use App\Enums\TipoGarantia;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Garantias\Pages\ManageGarantias;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Garantia;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class GarantiaResource extends Resource
{
    use ControlaPermisos;

    public const ESTADOS = ['vigente' => 'Vigente', 'reclamada' => 'Reclamada', 'vencida' => 'Vencida'];

    protected static string $modulo = 'crm';

    protected static ?string $model = Garantia::class;

    protected static ?string $slug = 'garantias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 4;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'garantía';

    protected static ?string $pluralModelLabel = 'Garantías';

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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['orden', 'vehiculo.cliente', 'ordenReclamo']))
            ->columns([
                TextColumn::make('vehiculo.descripcion')->label('Vehículo')
                    ->description(fn (Garantia $r) => $r->vehiculo->cliente?->nombre_completo)
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('vehiculo', fn ($v) => $v->where('placas', 'like', "%{$search}%")->orWhere('vin', 'like', "%{$search}%"))),
                TextColumn::make('tipo')->label('Tipo')->badge()->color('info'),
                TextColumn::make('orden.folio')->label('Orden')
                    ->url(fn (Garantia $r) => OrdenServicioResource::getUrl('view', ['record' => $r->orden_servicio_id])),
                TextColumn::make('inicia')->label('Desde')->date('d/m/Y'),
                TextColumn::make('vence')->label('Vence')->date('d/m/Y')->sortable()
                    ->description(fn (Garantia $r) => $r->km_limite ? 'o a los '.number_format($r->km_limite).' km' : null)
                    ->color(fn (Garantia $r) => $r->estado === 'vigente' && $r->vence->lte(today()->addDays(15)) ? 'warning' : null),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => self::ESTADOS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'vigente' => 'success', 'reclamada' => 'warning', default => 'gray'
                    })
                    ->description(fn (Garantia $r) => $r->ordenReclamo?->folio),
            ])
            ->filters([
                SelectFilter::make('estado')->options(self::ESTADOS)->default('vigente'),
                SelectFilter::make('tipo')->options(TipoGarantia::class),
            ])
            ->defaultSort('vence')
            ->recordActions([
                Action::make('reclamar')->label('Reclamar')->icon(Heroicon::OutlinedExclamationTriangle)->color('warning')
                    ->visible(fn (Garantia $r) => $r->estado === 'vigente' && auth()->user()->can('recepcion.gestionar'))
                    ->modalDescription('Se abrirá una orden de tipo garantía (sin cargo al cliente) para el mismo vehículo.')
                    ->schema([Textarea::make('motivo')->label('¿Qué falla reporta el cliente?')->required()->rows(3)])
                    ->action(function (Garantia $r, array $data, $livewire) {
                        $orden = (new ReclamarGarantia)($r, $data['motivo'], auth()->user());
                        $livewire->redirect(OrdenServicioResource::getUrl('view', ['record' => $orden]));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGarantias::route('/')];
    }
}
