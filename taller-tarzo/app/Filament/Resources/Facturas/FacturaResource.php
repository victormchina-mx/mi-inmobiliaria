<?php

namespace App\Filament\Resources\Facturas;

use App\Enums\EstadoFactura;
use App\Enums\FormaPago;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\Facturas\Pages\EditFactura;
use App\Filament\Resources\Facturas\Pages\ListFacturas;
use App\Filament\Resources\Facturas\Pages\ViewFactura;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\Factura;
use App\Models\UsoCfdi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FacturaResource extends Resource
{
    use ControlaPermisos;

    public const TIPOS = ['I' => 'Factura', 'E' => 'Nota de crédito', 'P' => 'Complemento de pago'];

    protected static string $modulo = 'facturacion';

    protected static ?string $model = Factura::class;

    protected static ?string $slug = 'facturas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Facturación';

    protected static ?int $navigationSort = 1;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'factura';

    protected static ?string $pluralModelLabel = 'Facturas';

    protected static ?string $recordTitleAttribute = 'folio_completo';

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && $record->editable() && $record->tipo !== Factura::PAGO;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Factura::whereIn('estado', [EstadoFactura::Borrador->value, EstadoFactura::Error->value])->count();

        return $n ? (string) $n : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Receptor')->columns(2)->schema([
                TextInput::make('receptor_rfc')->label('RFC')->required()->maxLength(13),
                TextInput::make('receptor_nombre')->label('Nombre o razón social')->required(),
                TextInput::make('receptor_regimen')->label('Régimen fiscal')->required()->length(3),
                TextInput::make('receptor_cp')->label('Código postal fiscal')->required()->length(5),
                Select::make('uso_cfdi')->label('Uso del CFDI')->options(fn () => UsoCfdi::pluck('descripcion', 'clave')->map(fn ($d, $c) => "{$c} · {$d}"))->searchable()->required(),
                TextInput::make('email')->label('Correo para enviar la factura')->email(),
            ]),
            Section::make('Pago')->columns(3)->schema([
                Select::make('metodo_pago')->label('Método')->options(['PUE' => 'PUE · Una sola exhibición', 'PPD' => 'PPD · Parcialidades o diferido'])->required()->live(),
                Select::make('forma_pago')->label('Forma de pago')->options(FormaPago::class)->required()
                    ->helperText('Con PPD la forma debe ser 99 · Por definir.')
                    ->rule(fn ($get) => fn ($attr, $value, $fail) => $get('metodo_pago') === 'PPD' && ($value instanceof FormaPago ? $value->value : $value) !== '99' ? $fail('Con PPD la forma de pago debe ser 99.') : null),
                DatePicker::make('vence')->label('Fecha de pago (crédito)'),
            ]),
            Repeater::make('conceptos')->relationship()->label('Conceptos')->columnSpanFull()->addable(false)->deletable(false)->reorderable(false)
                ->helperText('Los importes vienen de la cotización autorizada. Aquí solo se ajustan claves SAT y descripción.')
                ->schema([
                    TextInput::make('clave_prod_serv')->label('Clave producto/servicio')->required()->length(8),
                    TextInput::make('clave_unidad')->label('Clave unidad')->required()->maxLength(3),
                    TextInput::make('unidad')->label('Unidad')->required()->maxLength(30),
                    TextInput::make('importe')->label('Importe')->prefix('$')->disabled()->dehydrated(false),
                    Textarea::make('descripcion')->label('Descripción')->required()->rows(1)->columnSpanFull(),
                ])->columns(4),
            Textarea::make('notas')->label('Notas internas')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('estado')->label('Estado')->badge(),
                TextEntry::make('tipo')->label('Tipo')->formatStateUsing(fn ($state) => self::TIPOS[$state] ?? $state),
                TextEntry::make('uuid')->label('Folio fiscal (UUID)')->placeholder('Sin timbrar')->copyable()->columnSpan(2),
                TextEntry::make('receptor_nombre')->label('Receptor')->belowContent(fn (Factura $r) => "RFC {$r->receptor_rfc} · régimen {$r->receptor_regimen} · C.P. {$r->receptor_cp} · uso {$r->uso_cfdi}")->columnSpan(2),
                TextEntry::make('metodo_pago')->label('Método / forma')->formatStateUsing(fn (Factura $r) => $r->metodo_pago.' · '.$r->forma_pago?->getLabel())->placeholder('—'),
                TextEntry::make('orden.folio')->label('Orden')->placeholder('—')
                    ->url(fn (Factura $r) => $r->orden_servicio_id ? OrdenServicioResource::getUrl('view', ['record' => $r->orden_servicio_id]) : null),
                TextEntry::make('subtotal')->label('Subtotal')->money('MXN'),
                TextEntry::make('descuento')->label('Descuento')->money('MXN'),
                TextEntry::make('iva')->label('IVA')->money('MXN'),
                TextEntry::make('total')->label('Total')->money('MXN')->weight('bold'),
                TextEntry::make('saldo')->label('Saldo por cobrar')->money('MXN')->visible(fn (Factura $r) => $r->tipo === Factura::INGRESO)
                    ->color(fn (Factura $r) => $r->saldo > 0 ? 'warning' : 'success'),
                TextEntry::make('vence')->label('Vence')->date('d/m/Y')->placeholder('—'),
                TextEntry::make('relacionada.folio_completo')->label('CFDI relacionado')->placeholder('—')
                    ->formatStateUsing(fn (Factura $r, $state) => $state ? "{$state} (relación {$r->relacion_tipo})" : null),
                TextEntry::make('motivo_cancelacion')->label('Cancelación')->placeholder('—')
                    ->formatStateUsing(fn (Factura $r) => "Motivo {$r->motivo_cancelacion} · {$r->estado_cancelacion}"),
                TextEntry::make('error')->label('Error del PAC')->color('danger')->columnSpanFull()->visible(fn (Factura $r) => filled($r->error)),
                TextEntry::make('notas')->label('Notas')->placeholder('—')->columnSpanFull(),
            ]),
            RepeatableEntry::make('conceptos')->label('Conceptos')->columnSpanFull()->visible(fn (Factura $r) => $r->tipo !== Factura::PAGO)
                ->table([
                    RepeatableEntry\TableColumn::make('Clave'), RepeatableEntry\TableColumn::make('Descripción'),
                    RepeatableEntry\TableColumn::make('Cantidad'), RepeatableEntry\TableColumn::make('Importe'),
                    RepeatableEntry\TableColumn::make('Descuento'), RepeatableEntry\TableColumn::make('IVA'),
                ])
                ->schema([
                    TextEntry::make('clave_prod_serv')->formatStateUsing(fn ($record) => "{$record->clave_prod_serv} / {$record->clave_unidad}"),
                    TextEntry::make('descripcion'),
                    TextEntry::make('cantidad')->numeric(2),
                    TextEntry::make('importe')->money('MXN'),
                    TextEntry::make('descuento')->money('MXN'),
                    TextEntry::make('iva')->money('MXN'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('orden'))
            ->columns([
                TextColumn::make('folio')->label('Folio')->formatStateUsing(fn (Factura $r) => $r->folio_completo)
                    ->description(fn (Factura $r) => self::TIPOS[$r->tipo])->sortable()->searchable(['folio', 'uuid']),
                TextColumn::make('receptor_nombre')->label('Receptor')->description(fn (Factura $r) => $r->receptor_rfc)->searchable(['receptor_nombre', 'receptor_rfc'])->wrap(),
                TextColumn::make('orden.folio')->label('Orden')->placeholder('—'),
                TextColumn::make('total')->label('Total')->money('MXN')->sortable(),
                TextColumn::make('saldo')->label('Saldo')->money('MXN')->color(fn ($state) => $state > 0 ? 'warning' : null)
                    ->state(fn (Factura $r) => $r->tipo === Factura::INGRESO ? $r->saldo : null)->placeholder('—'),
                TextColumn::make('metodo_pago')->label('Método')->placeholder('—'),
                TextColumn::make('timbrada_at')->label('Timbrada')->date('d/m/Y')->placeholder('—')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->filters([
                SelectFilter::make('tipo')->options(self::TIPOS),
                SelectFilter::make('estado')->options(EstadoFactura::class),
                SelectFilter::make('receptor_tipo')->label('Receptor')->options(['cliente' => 'Clientes', 'aseguradora' => 'Aseguradoras']),
                SelectFilter::make('orden_servicio_id')->label('Orden')->relationship('orden', 'folio')->searchable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([ViewAction::make()->label('Ver'), EditAction::make()->label('Editar')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacturas::route('/'),
            'view' => ViewFactura::route('/{record}'),
            'edit' => EditFactura::route('/{record}/edit'),
        ];
    }
}
