<?php

namespace App\Filament\Resources\Checkins\Schemas;

use App\Enums\ElementoInventario;
use App\Enums\Testigo;
use App\Enums\TipoDano;
use App\Enums\ZonaVehiculo;
use App\Models\Checkin;
use App\Support\Media;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CheckinForm
{
    /** Elementos que casi siempre trae un auto; el asesor desmarca lo que falte. */
    public const INVENTARIO_HABITUAL = ['llanta_refaccion', 'gato', 'llave_birlos', 'tapetes', 'estereo', 'tapon_gasolina', 'tarjeta_circulacion'];

    public static function configure(Schema $schema): Schema
    {
        $directorio = fn (?Checkin $record) => 'checkins/'.($record?->orden?->folio ?? 'sin-folio');
        $foto = fn (string $campo) => FileUpload::make($campo)
            ->image()
            ->disk(fn () => Media::nombreDisco())->visibility('private')
            ->directory($directorio)
            ->automaticallyResizeImagesToWidth((string) config('tarzo.media.max_foto_px'))
            ->maxSize(15360);

        return $schema->columns(1)->components([
            Callout::make('Inventario firmado')
                ->description(fn (?Checkin $record) => $record?->estaFirmado()
                    ? "Firmado por {$record->firmado_por} el {$record->firmado_at->format('d/m/Y H:i')}. Ya no se puede modificar."
                    : null)
                ->success()
                ->visible(fn (?Checkin $record) => (bool) $record?->estaFirmado()),

            Section::make('Fotos del vehículo')
                ->description('Frente, atrás, ambos lados, interior y tablero. Se reducen en el celular antes de subirse.')
                ->schema([
                    $foto('fotos')->hiddenLabel()->multiple()->maxFiles(16)->reorderable()->panelLayout('grid')->appendFiles(),
                ]),

            Section::make('Daños previos')
                ->description('Marca cada rayón, golpe o pieza faltante con su foto. Las zonas marcadas se pintan en rojo.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 4])->schema([
                        View::make('filament.checkin.diagrama-en-vivo')
                            ->viewData(fn (Get $get) => ['zonas' => collect($get('danos') ?? [])->pluck('zona')->filter()->values()->all()])
                            ->columnSpan(1),
                        Repeater::make('danos')->hiddenLabel()->relationship('danos')->orderColumn('orden')
                            ->schema([
                                Select::make('zona')->label('Zona')->options(ZonaVehiculo::class)->required()->searchable()->live(),
                                ToggleButtons::make('tipo')->label('Tipo de daño')->options(TipoDano::class)->inline()->required(),
                                TextInput::make('descripcion')->label('Descripción')->maxLength(255)->placeholder('Rayón de 10 cm cerca de la manija'),
                                $foto('foto')->label('Foto'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state) => isset($state['zona'])
                                ? (ZonaVehiculo::tryFrom($state['zona'] instanceof ZonaVehiculo ? $state['zona']->value : $state['zona'])?->getLabel() ?? 'Daño')
                                : 'Nuevo daño')
                            ->collapsible()
                            ->addActionLabel('Agregar daño')
                            ->defaultItems(0)
                            ->live()
                            ->columnSpan(['default' => 1, 'md' => 3]),
                    ]),
                ]),

            Section::make('Tablero')->columns(2)->schema([
                ToggleButtons::make('nivel_combustible')->label('Nivel de gasolina')
                    ->options([0 => 'Vacío', 1 => '1/8', 2 => '1/4', 3 => '3/8', 4 => '1/2', 5 => '5/8', 6 => '3/4', 7 => '7/8', 8 => 'Lleno'])
                    ->inline()->required()->columnSpanFull(),
                TextInput::make('kilometraje')->label('Kilometraje')->numeric()->minValue(0)->suffix('km')->required(),
                $foto('foto_odometro')->label('Foto del odómetro'),
                CheckboxList::make('testigos')->label('Testigos encendidos')
                    ->options(Testigo::class)->columns(['default' => 2, 'md' => 4])->columnSpanFull(),
            ]),

            Section::make('Inventario y pertenencias')->schema([
                CheckboxList::make('inventario')->label('Se recibe con')
                    ->options(ElementoInventario::class)->columns(['default' => 2, 'md' => 3])->bulkToggleable(),
                Textarea::make('pertenencias')->label('Pertenencias declaradas por el cliente')->rows(2)
                    ->placeholder('Lentes en la guantera, silla para bebé…'),
                Textarea::make('observaciones')->label('Observaciones del asesor')->rows(2),
            ]),

            Section::make('Video de recorrido')->collapsible()->collapsed()->schema([
                FileUpload::make('video')->hiddenLabel()
                    ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm', 'video/3gpp'])
                    ->maxSize(fn () => config('tarzo.media.max_video_mb') * 1024)
                    ->disk(fn () => Media::nombreDisco())->visibility('private')->directory($directorio)
                    ->helperText('Hasta 90 segundos alrededor del vehículo (máx. '.config('tarzo.media.max_video_mb').' MB).'),
            ]),

            Section::make('Firma del cliente')
                ->description('Firma aquí en la tableta, o usa "Enviar a firma" para que el cliente firme desde su celular.')
                ->visible(fn (?Checkin $record) => ! $record?->estaFirmado())
                ->schema([
                    View::make('filament.checkin.textos-legales'),
                    Checkbox::make('firma_acepta_condiciones')->label('El cliente acepta las condiciones del servicio y que el inventario refleja el estado del vehículo.')
                        ->dehydrated(false),
                    Checkbox::make('firma_acepta_aviso')->label('El cliente leyó el aviso de privacidad.')
                        ->dehydrated(false),
                    TextInput::make('firma_nombre')->label('Nombre de quien firma')
                        ->default(fn (?Checkin $record) => $record?->orden?->cliente?->nombre_completo)
                        ->maxLength(160)->dehydrated(false),
                    ViewField::make('firma_tableta')->label('Firma')->view('filament.checkin.firma-pad')->dehydrated(false),
                ]),
        ])->disabled(fn (?Checkin $record) => (bool) $record?->estaFirmado());
    }
}
