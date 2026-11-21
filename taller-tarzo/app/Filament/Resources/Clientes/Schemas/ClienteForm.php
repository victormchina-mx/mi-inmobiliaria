<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Enums\CanalPreferido;
use App\Enums\TipoPersona;
use App\Models\Cliente;
use App\Models\RegimenFiscal;
use App\Models\UsoCfdi;
use App\Rules\RfcValido;
use App\Support\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ClienteForm
{
    /** Acepta formatos comunes: 7221234567, 722 123 4567, +52 (722) 123-4567. */
    public const REGEX_TELEFONO = '/^[+\d\s().\-]{7,20}$/';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos generales')->columns(2)->schema([
                ToggleButtons::make('tipo_persona')->label('Tipo de cliente')
                    ->options(TipoPersona::class)->inline()->default(TipoPersona::Fisica)->live()->required()
                    ->columnSpanFull(),
                TextInput::make('nombre')
                    ->label(fn (Get $get) => self::esMoral($get) ? 'Razón social o nombre comercial' : 'Nombre(s)')
                    ->required()->maxLength(160),
                TextInput::make('apellidos')->label('Apellidos')->maxLength(160)
                    ->hidden(fn (Get $get) => self::esMoral($get)),
                Select::make('flotilla_id')->label('Flotilla')->relationship('flotilla', 'nombre')
                    ->searchable()->preload()->placeholder('No pertenece a una flotilla'),
            ]),

            Section::make('Contacto')->columns(2)->schema([
                TextInput::make('telefono')->label('Teléfono')->tel()->telRegex(self::REGEX_TELEFONO)->maxLength(20)
                    ->placeholder('722 123 4567')
                    ->live(onBlur: true)
                    ->hint(fn (Get $get, ?Cliente $record) => self::avisoDuplicado($get('telefono'), null, $record))
                    ->hintColor('warning'),
                TextInput::make('whatsapp')->label('WhatsApp')->tel()->telRegex(self::REGEX_TELEFONO)->maxLength(20)
                    ->helperText('Déjalo vacío si es el mismo teléfono.'),
                TextInput::make('email')->label('Correo electrónico')->email()->maxLength(255)
                    ->live(onBlur: true)
                    ->hint(fn (Get $get, ?Cliente $record) => self::avisoDuplicado(null, $get('email'), $record))
                    ->hintColor('warning'),
                ToggleButtons::make('canal_preferido')->label('Canal preferido para avisos')
                    ->options(CanalPreferido::class)->inline()->default(CanalPreferido::WhatsApp)->required(),
                TextInput::make('calle')->label('Calle y número')->maxLength(255),
                TextInput::make('colonia')->label('Colonia')->maxLength(120),
                TextInput::make('municipio')->label('Municipio')->maxLength(120),
                TextInput::make('estado')->label('Estado')->maxLength(80)->default('Estado de México'),
                TextInput::make('codigo_postal')->label('Código postal')->numeric()->length(5),
                Toggle::make('acepta_promociones')->label('Acepta recibir promociones')->inline(false),
            ]),

            Section::make('Datos fiscales (CFDI 4.0)')
                ->description('Captúralos exactamente como aparecen en la Constancia de Situación Fiscal. Solo si el cliente pide factura.')
                ->collapsible()
                ->columns(2)
                ->relationship('datosFiscales', condition: fn (?array $state): bool => filled($state['rfc'] ?? null))
                ->schema([
                    TextInput::make('rfc')->label('RFC')->maxLength(13)->rule(new RfcValido)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                    TextInput::make('nombre_fiscal')->label('Nombre o razón social fiscal')
                        ->helperText('Sin "S.A. de C.V." ni otro régimen societario.')
                        ->requiredWith('rfc')->maxLength(255),
                    Select::make('regimen_fiscal')->label('Régimen fiscal')
                        ->options(fn (Get $get) => self::regimenes($get))
                        ->searchable()->requiredWith('rfc'),
                    TextInput::make('codigo_postal')->label('Código postal fiscal')->numeric()->length(5)->requiredWith('rfc'),
                    Select::make('uso_cfdi')->label('Uso de CFDI habitual')
                        ->options(fn (Get $get) => self::usos($get))
                        ->default('G03')->searchable()->requiredWith('rfc'),
                    TextInput::make('email_facturacion')->label('Correo para facturas')->email(),
                    FileUpload::make('constancia_path')->label('Constancia de Situación Fiscal (PDF)')
                        ->disk(fn () => Media::nombreDisco())->directory('constancias')->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])->maxSize(4096)->openable()->downloadable()
                        ->columnSpanFull(),
                ]),

            Section::make('Notas y preferencias')->collapsible()->schema([
                Textarea::make('notas')->label('Notas')->rows(3)
                    ->placeholder('Ej.: solo refacciones originales; llamar después de las 5 pm.'),
            ]),
        ]);
    }

    private static function esMoral(Get $get): bool
    {
        $tipo = $get('tipo_persona');

        return ($tipo instanceof TipoPersona ? $tipo : TipoPersona::tryFrom((string) $tipo)) === TipoPersona::Moral;
    }

    /**
     * @return array<string, string>
     */
    private static function regimenes(Get $get): array
    {
        $columna = self::esMoralFiscal($get) ? 'persona_moral' : 'persona_fisica';

        return RegimenFiscal::where($columna, true)->orderBy('clave')->get()
            ->mapWithKeys(fn ($r) => [$r->clave => "{$r->clave} · {$r->descripcion}"])->all();
    }

    /**
     * @return array<string, string>
     */
    private static function usos(Get $get): array
    {
        $columna = self::esMoralFiscal($get) ? 'persona_moral' : 'persona_fisica';

        return UsoCfdi::where($columna, true)->orderBy('clave')->get()
            ->mapWithKeys(fn ($u) => [$u->clave => "{$u->clave} · {$u->descripcion}"])->all();
    }

    /**
     * Dentro de la sección fiscal, el tipo de persona se deduce del RFC (12 = moral) o del tipo de cliente.
     */
    private static function esMoralFiscal(Get $get): bool
    {
        $rfc = (string) $get('rfc');

        if (strlen($rfc) >= 12) {
            return strlen(preg_replace('/\s/', '', $rfc)) === 12;
        }

        $tipo = $get('../tipo_persona');

        return ($tipo instanceof TipoPersona ? $tipo : TipoPersona::tryFrom((string) $tipo)) === TipoPersona::Moral;
    }

    private static function avisoDuplicado(?string $telefono, ?string $email, ?Cliente $actual): ?string
    {
        $duplicados = Cliente::posiblesDuplicados($telefono, $email, $actual?->id);

        if ($duplicados->isEmpty()) {
            return null;
        }

        return 'Ya registrado: '.$duplicados->pluck('nombre_completo')->implode(', ');
    }
}
