<?php

namespace App\Filament\Resources\OrdenServicios\Schemas;

use App\Enums\TipoOrden;
use App\Filament\Resources\Vehiculos\Schemas\VehiculoForm;
use App\Models\Poliza;
use App\Models\Vehiculo;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrdenServicioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vehículo')
                ->description('Busca por placas, VIN, modelo o nombre del dueño. Si es la primera visita, créalo aquí mismo.')
                ->schema([
                    Select::make('vehiculo_id')->label('Vehículo')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => self::buscarVehiculos($search))
                        ->getOptionLabelUsing(fn ($value) => self::etiquetaVehiculo(Vehiculo::with('cliente')->find($value)))
                        ->createOptionForm(fn (Schema $schema) => $schema->columns(3)->components([
                            VehiculoForm::selectCliente()->columnSpanFull(),
                            ...VehiculoForm::campos(),
                        ]))
                        ->createOptionUsing(fn (array $data) => Vehiculo::create($data)->getKey())
                        ->createOptionModalHeading('Nuevo vehículo')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set) {
                            $vehiculo = $state ? Vehiculo::find($state) : null;
                            $set('kilometraje_ingreso', $vehiculo?->kilometraje);
                            $poliza = $vehiculo?->polizas()->latest('vigencia_fin')->first();
                            $set('poliza_id', $poliza?->id);
                            $set('aseguradora_id', $poliza?->aseguradora_id);
                        })
                        ->required()
                        ->disabledOn('edit'),
                ]),

            Section::make('Servicio')->columns(2)->schema([
                ToggleButtons::make('tipo')->label('Tipo de orden')->options(TipoOrden::class)
                    ->default(TipoOrden::Particular)->inline()->live()->required()->columnSpanFull(),
                Select::make('aseguradora_id')->label('Aseguradora')
                    ->relationship('aseguradora', 'nombre', fn (Builder $query) => $query->where('activo', true))->preload()
                    ->visible(fn (Get $get) => self::esSeguro($get))->required(fn (Get $get) => self::esSeguro($get)),
                TextInput::make('numero_siniestro')->label('Número de siniestro')->maxLength(60)
                    ->visible(fn (Get $get) => self::esSeguro($get)),
                Select::make('poliza_id')->label('Póliza')
                    ->options(fn (Get $get) => $get('vehiculo_id')
                        ? Poliza::with('aseguradora')->where('vehiculo_id', $get('vehiculo_id'))->get()
                            ->mapWithKeys(fn (Poliza $p) => [$p->id => "{$p->aseguradora->nombre} · {$p->numero}".($p->estaVigente() ? '' : ' (vencida)')])
                        : [])
                    ->visible(fn (Get $get) => self::esSeguro($get))
                    ->helperText('Las pólizas se registran en la ficha del vehículo.'),
                TextInput::make('kilometraje_ingreso')->label('Kilometraje de ingreso')->numeric()->minValue(0)->suffix('km'),
                DateTimePicker::make('fecha_promesa')->label('Fecha promesa de entrega')->native(false)
                    ->displayFormat('d/m/Y H:i')->seconds(false)->minDate(now()->startOfDay()),
                Select::make('asesor_id')->label('Asesor')->relationship('asesor', 'name', fn (Builder $query) => $query->where('activo', true))
                    ->default(fn () => auth()->id())->preload(),
                Textarea::make('motivo_ingreso')->label('Motivo de ingreso / síntomas reportados')->rows(3)
                    ->placeholder('Lo que el cliente describe: ruido al frenar, golpe en facia delantera, servicio de 60,000 km…')
                    ->required()->columnSpanFull(),
            ]),
        ]);
    }

    private static function esSeguro(Get $get): bool
    {
        $tipo = $get('tipo');

        return ($tipo instanceof TipoOrden ? $tipo : TipoOrden::tryFrom((string) $tipo)) === TipoOrden::Seguro;
    }

    /**
     * @return array<int, string>
     */
    public static function buscarVehiculos(string $termino): array
    {
        $limpio = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $termino));
        $texto = '%'.trim($termino).'%';

        return Vehiculo::with('cliente')
            ->where(fn (Builder $q) => $q
                ->when($limpio !== '', fn ($query) => $query->where('placas', 'like', "%{$limpio}%")->orWhere('vin', 'like', "%{$limpio}%"))
                ->orWhere('modelo', 'like', $texto)
                ->orWhereHas('cliente', fn (Builder $c) => $c->where('nombre', 'like', $texto)->orWhere('apellidos', 'like', $texto)->orWhere('telefono', 'like', $texto)))
            ->limit(25)->get()
            ->mapWithKeys(fn (Vehiculo $v) => [$v->id => self::etiquetaVehiculo($v)])
            ->all();
    }

    public static function etiquetaVehiculo(?Vehiculo $v): ?string
    {
        return $v ? "{$v->descripcion} — {$v->cliente?->nombre_completo}" : null;
    }
}
