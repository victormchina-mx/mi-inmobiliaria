<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use App\Filament\Resources\Clientes\Schemas\ClienteForm;
use App\Models\Vehiculo;
use App\Rules\VinValido;
use App\Services\DecodificadorVin;
use App\Support\Vin;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dueño')->schema([self::selectCliente()]),
            Section::make('Vehículo')->columns(3)->schema(self::campos()),
        ]);
    }

    public static function selectCliente(): Select
    {
        return Select::make('cliente_id')->label('Cliente')
            ->relationship('cliente', 'nombre')
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo.($record->telefono ? " · {$record->telefono}" : ''))
            ->searchable(['nombre', 'apellidos', 'telefono', 'email'])
            ->preload(false)
            ->createOptionForm(fn (Schema $schema) => ClienteForm::configure($schema))
            ->createOptionModalHeading('Nuevo cliente')
            ->required();
    }

    /**
     * Campos del vehículo, reutilizados en el recurso, en el alta desde cliente y en la orden.
     *
     * @return array<int, mixed>
     */
    public static function campos(): array
    {
        return [
            TextInput::make('vin')->label('VIN (número de serie)')
                ->maxLength(17)->rule(new VinValido)
                ->unique(table: 'vehiculos', column: 'vin', ignoreRecord: true)
                ->extraInputAttributes(['style' => 'text-transform:uppercase', 'autocomplete' => 'off'])
                ->live(onBlur: true)
                ->hint(fn (?string $state) => filled($state) && Vin::formatoValido($state) && ! Vin::digitoVerificadorValido($state)
                    ? 'El dígito verificador no coincide: revisa el VIN (algunos importados no lo cumplen).'
                    : null)
                ->hintColor('warning')
                ->suffixAction(
                    Action::make('decodificar')->label('Decodificar VIN')->icon(Heroicon::OutlinedSparkles)
                        ->action(function (?string $state, Set $set): void {
                            $datos = filled($state) ? app(DecodificadorVin::class)->decodificar($state) : null;

                            if ($datos === null) {
                                Notification::make()->title('No se encontraron datos para este VIN')
                                    ->body('Captura marca, modelo y año a mano.')->warning()->send();

                                return;
                            }

                            foreach ($datos as $campo => $valor) {
                                if (filled($valor)) {
                                    $set($campo, $valor);
                                }
                            }

                            Notification::make()->title('Datos del VIN cargados')->success()->send();
                        })
                )
                ->columnSpan(2),
            TextInput::make('placas')->label('Placas')->maxLength(12)
                ->extraInputAttributes(['style' => 'text-transform:uppercase']),
            TextInput::make('marca')->label('Marca')->required()->maxLength(60)
                ->datalist(fn () => Vehiculo::query()->distinct()->orderBy('marca')->limit(50)->pluck('marca')->all()),
            TextInput::make('modelo')->label('Modelo')->required()->maxLength(80),
            TextInput::make('anio')->label('Año')->numeric()->minValue(1950)->maxValue((int) date('Y') + 1),
            TextInput::make('version')->label('Versión')->maxLength(80),
            TextInput::make('color')->label('Color')->maxLength(40),
            TextInput::make('motor')->label('Motor')->maxLength(40),
            Select::make('transmision')->label('Transmisión')->options(['Automática' => 'Automática', 'Estándar' => 'Estándar', 'CVT' => 'CVT']),
            TextInput::make('kilometraje')->label('Kilometraje')->numeric()->minValue(0)->suffix('km'),
            Textarea::make('notas')->label('Notas')->rows(2)->columnSpanFull(),
        ];
    }
}
