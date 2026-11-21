<?php

namespace App\Filament\Resources\PlantillasMensaje;

use App\Enums\EventoMensaje;
use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\PlantillasMensaje\Pages\ManagePlantillasMensaje;
use App\Models\PlantillaMensaje;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PlantillaMensajeResource extends Resource
{
    use ControlaPermisos;

    public const VARIABLES = '{cliente} {cliente_completo} {taller} {telefono_taller} {folio} {vehiculo} {placas} {etapa} {fecha_promesa} {enlace} · '
        .'presupuesto: {total} {enlace_aprobacion} · recordatorios: {servicio} {km} · encuesta: {enlace_encuesta}';

    protected static string $modulo = 'crm';

    protected static ?string $model = PlantillaMensaje::class;

    protected static ?string $slug = 'plantillas-mensaje';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 8;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'plantilla de mensaje';

    protected static ?string $pluralModelLabel = 'Plantillas de mensajes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('evento')->label('Evento')->options(EventoMensaje::class)->required(),
            Select::make('canal')->label('Canal')->options(['whatsapp' => 'WhatsApp', 'correo' => 'Correo'])->required()->live(),
            TextInput::make('asunto')->label('Asunto del correo')->maxLength(200)->visible(fn (Get $get) => $get('canal') === 'correo'),
            Textarea::make('cuerpo')->label('Texto')->rows(5)->required()->columnSpanFull()
                ->helperText('Variables: '.self::VARIABLES.'. En WhatsApp este texto es de referencia: Meta envía la plantilla aprobada.'),
            TextInput::make('plantilla_meta')->label('Nombre de la plantilla aprobada en Meta')->maxLength(120)
                ->visible(fn (Get $get) => $get('canal') === 'whatsapp'),
            TagsInput::make('variables_meta')->label('Variables de la plantilla de Meta, en orden ({{1}}, {{2}}…)')
                ->placeholder('cliente')->visible(fn (Get $get) => $get('canal') === 'whatsapp'),
            TextInput::make('idioma')->label('Idioma')->default('es_MX')->maxLength(10)->visible(fn (Get $get) => $get('canal') === 'whatsapp'),
            Toggle::make('activo')->label('Activa')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('evento')->label('Evento')->badge()->sortable(),
                TextColumn::make('canal')->label('Canal')->formatStateUsing(fn ($state) => $state === 'whatsapp' ? 'WhatsApp' : 'Correo'),
                TextColumn::make('cuerpo')->label('Texto')->limit(80)->wrap(),
                TextColumn::make('plantilla_meta')->label('Plantilla Meta')->placeholder('—'),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->filters([SelectFilter::make('canal')->options(['whatsapp' => 'WhatsApp', 'correo' => 'Correo'])])
            ->defaultSort('evento')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePlantillasMensaje::route('/')];
    }
}
