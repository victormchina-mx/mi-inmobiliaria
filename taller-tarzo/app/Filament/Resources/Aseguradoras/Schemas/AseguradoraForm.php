<?php

namespace App\Filament\Resources\Aseguradoras\Schemas;

use App\Models\RegimenFiscal;
use App\Models\UsoCfdi;
use App\Rules\RfcValido;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AseguradoraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre comercial')->required()->maxLength(120)->unique(ignoreRecord: true),
            TextInput::make('rfc')->label('RFC')->maxLength(13)->rule(new RfcValido),
            TextInput::make('telefono')->label('Teléfono de siniestros')->tel()->maxLength(20),
            TextInput::make('email')->label('Correo para facturas y valuaciones')->email(),
            TextInput::make('dias_pago')->label('Días de pago')->helperText('Plazo habitual entre la factura y el pago.')->numeric()->minValue(0)->default(30)->required(),
            Toggle::make('activo')->label('Activa')->default(true),
            Textarea::make('notas')->label('Notas')->rows(3)->columnSpanFull(),

            Section::make('Datos para facturar (CFDI 4.0)')
                ->description('Deben coincidir con la constancia de situación fiscal de la aseguradora.')
                ->columns(2)->columnSpanFull()->collapsible()
                ->schema([
                    TextInput::make('razon_social')->label('Razón social (sin régimen societario)')->maxLength(255)->placeholder('QUALITAS COMPAÑIA DE SEGUROS'),
                    Select::make('regimen_fiscal')->label('Régimen fiscal')
                        ->options(fn () => RegimenFiscal::where('persona_moral', true)->pluck('descripcion', 'clave')->map(fn ($d, $c) => "{$c} · {$d}"))
                        ->searchable(),
                    TextInput::make('codigo_postal')->label('Código postal fiscal')->numeric()->length(5),
                    Select::make('uso_cfdi')->label('Uso del CFDI')
                        ->options(fn () => UsoCfdi::where('persona_moral', true)->pluck('descripcion', 'clave')->map(fn ($d, $c) => "{$c} · {$d}"))
                        ->default('G03')->searchable(),
                    TextInput::make('email_facturacion')->label('Correo para recibir facturas')->email(),
                ]),

            Section::make('Tabulador pactado')
                ->description('Se aplica automáticamente a las partidas que paga esta aseguradora.')
                ->relationship('tabulador')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('descuento_mano_obra')->label('Descuento en mano de obra')->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0),
                    TextInput::make('descuento_refacciones')->label('Descuento en refacciones')->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0),
                    TextInput::make('factor_materiales')->label('Factor de materiales de pintura')->numeric()->step(0.05)->minValue(0)->default(1)
                        ->helperText('1 = costo normal; 0.9 = paga 10 % menos.'),
                    Repeater::make('tarifas')->label('Tarifa por hora pactada por área')
                        ->relationship()
                        ->schema([
                            Select::make('area_id')->label('Área')->relationship('area', 'nombre')->required()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            TextInput::make('tarifa_hora')->label('Tarifa por hora')->numeric()->prefix('$')->minValue(0)->required(),
                        ])
                        ->columns(2)->columnSpanFull()->addActionLabel('Agregar tarifa')->defaultItems(0)
                        ->helperText('Las áreas sin tarifa pactada usan la tarifa normal del taller.'),
                    Textarea::make('conceptos_no_cubiertos')->label('Conceptos que no paga')->rows(2)->columnSpanFull()
                        ->placeholder('Ej.: pulido general, tapetes, accesorios no originales'),
                ]),
        ]);
    }
}
