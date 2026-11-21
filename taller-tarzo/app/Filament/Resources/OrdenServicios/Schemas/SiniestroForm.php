<?php

namespace App\Filament\Resources\OrdenServicios\Schemas;

use App\Enums\EstatusSiniestro;
use App\Support\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class SiniestroForm
{
    /**
     * @return array<int, mixed>
     */
    public static function campos(): array
    {
        return [
            Section::make('Siniestro')->columns(3)->schema([
                TextInput::make('numero')->label('Número de siniestro')->maxLength(60)->required(),
                Select::make('estatus')->label('Estatus')->options(EstatusSiniestro::class)->default(EstatusSiniestro::PorValuar)->required(),
                TextInput::make('folio_autorizacion')->label('Folio de autorización')->maxLength(60),
            ]),
            Section::make('Ajustador')->columns(3)->schema([
                TextInput::make('ajustador_nombre')->label('Nombre')->maxLength(160),
                TextInput::make('ajustador_telefono')->label('Teléfono / WhatsApp')->tel()->telRegex('/^[+\d\s().\-]{7,20}$/')->maxLength(20),
                TextInput::make('ajustador_email')->label('Correo')->email(),
            ]),
            Section::make('Montos a cargo del cliente')->columns(3)->schema([
                ToggleButtons::make('deducible_tipo')->label('Deducible')->options(['monto' => 'Monto fijo', 'porcentaje' => '% del valor'])
                    ->default('monto')->inline()->live()->required(),
                TextInput::make('deducible_valor')->label(fn (Get $get) => $get('deducible_tipo') === 'porcentaje' ? 'Porcentaje' : 'Monto (con IVA)')
                    ->numeric()->minValue(0)->default(0)->required()
                    ->prefix(fn (Get $get) => $get('deducible_tipo') === 'porcentaje' ? null : '$')
                    ->suffix(fn (Get $get) => $get('deducible_tipo') === 'porcentaje' ? '%' : null),
                TextInput::make('valor_comercial')->label('Valor comercial / suma asegurada')->numeric()->minValue(0)->prefix('$')
                    ->visible(fn (Get $get) => $get('deducible_tipo') === 'porcentaje')->required(fn (Get $get) => $get('deducible_tipo') === 'porcentaje'),
                TextInput::make('demerito')->label('Demérito')->helperText('Desgaste que paga el cliente (llantas, batería…).')->numeric()->minValue(0)->prefix('$')->default(0),
                TextInput::make('salvamento')->label('Salvamento')->helperText('Valor de piezas recuperadas que la aseguradora descuenta.')->numeric()->minValue(0)->prefix('$')->default(0),
            ]),
            FileUpload::make('orden_admision')->label('Orden de admisión o vale de reparación (PDF o foto)')
                ->disk(fn () => Media::nombreDisco())->directory('siniestros')->visibility('private')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(8192)->openable()->downloadable(),
            Textarea::make('notas')->label('Notas')->rows(2),
        ];
    }
}
