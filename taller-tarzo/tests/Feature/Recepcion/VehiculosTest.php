<?php

namespace Tests\Feature\Recepcion;

use App\Filament\Resources\Vehiculos\Pages\CreateVehiculo;
use App\Filament\Resources\Vehiculos\Pages\ViewVehiculo;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Services\DecodificadorVin;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class VehiculosTest extends RecepcionTestCase
{
    public function test_registra_un_vehiculo_con_vin_y_placas_normalizados(): void
    {
        $cliente = Cliente::factory()->create();

        Livewire::test(CreateVehiculo::class)
            ->fillForm([
                'cliente_id' => $cliente->id,
                'vin' => '1hgcm82633a004352',
                'placas' => 'mxa-123-a',
                'marca' => 'Honda',
                'modelo' => 'Accord',
                'anio' => 2003,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $v = Vehiculo::first();
        $this->assertSame('1HGCM82633A004352', $v->vin);
        $this->assertSame('MXA123A', $v->placas);
    }

    public function test_no_permite_vin_duplicado_ni_con_letras_prohibidas(): void
    {
        Vehiculo::factory()->create(['vin' => '1HGCM82633A004352']);
        $cliente = Cliente::factory()->create();

        Livewire::test(CreateVehiculo::class)
            ->fillForm(['cliente_id' => $cliente->id, 'vin' => '1HGCM82633A004352', 'marca' => 'Honda', 'modelo' => 'Accord'])
            ->call('create')
            ->assertHasFormErrors(['vin' => 'unique']);

        Livewire::test(CreateVehiculo::class)
            ->fillForm(['cliente_id' => $cliente->id, 'vin' => '1HGCM8263OA004352', 'marca' => 'Honda', 'modelo' => 'Accord'])
            ->call('create')
            ->assertHasFormErrors(['vin']);
    }

    public function test_decodifica_el_vin_con_nhtsa(): void
    {
        Http::fake(['vpic.nhtsa.dot.gov/*' => Http::response(['Results' => [[
            'Make' => 'NISSAN', 'Model' => 'Versa', 'ModelYear' => '2020', 'DisplacementL' => '1.6',
            'EngineCylinders' => '4', 'TransmissionStyle' => 'Continuously Variable Transmission (CVT)', 'Trim' => 'Advance',
        ]]])]);

        Livewire::test(CreateVehiculo::class)
            ->fillForm(['vin' => '3N1CN8AE0LL800000'])
            ->callAction(TestAction::make('decodificar')->schemaComponent('vin'))
            ->assertSchemaStateSet(['marca' => 'Nissan', 'modelo' => 'Versa', 'anio' => 2020, 'motor' => '1.6 L 4 cil.', 'version' => 'Advance']);
    }

    public function test_si_nhtsa_no_responde_no_rompe_nada(): void
    {
        Http::fake(['vpic.nhtsa.dot.gov/*' => Http::response(null, 500)]);

        $this->assertNull(app(DecodificadorVin::class)->decodificar('1HGCM82633A004352'));
    }

    public function test_cambia_de_dueno_conservando_el_historial(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $nuevo = Cliente::factory()->create();

        Livewire::test(ViewVehiculo::class, ['record' => $vehiculo->getRouteKey()])
            ->callAction('transferir', data: ['cliente_id' => $nuevo->id])
            ->assertHasNoActionErrors();

        $this->assertSame($nuevo->id, $vehiculo->fresh()->cliente_id);
    }
}
