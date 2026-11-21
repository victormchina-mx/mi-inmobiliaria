<?php

namespace Tests\Feature\Recepcion;

use App\Actions\Ordenes\AbrirOrden;
use App\Enums\TipoOrden;
use App\Filament\Resources\OrdenServicios\Pages\CreateOrdenServicio;
use App\Filament\Resources\OrdenServicios\Pages\ViewOrdenServicio;
use App\Filament\Resources\OrdenServicios\RelationManagers\DiagnosticosRelationManager;
use App\Filament\Resources\OrdenServicios\Schemas\OrdenServicioForm;
use App\Models\Aseguradora;
use App\Models\Diagnostico;
use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

class OrdenesRecepcionTest extends RecepcionTestCase
{
    public function test_recibir_vehiculo_abre_la_orden_y_lleva_al_check_in(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 40000]);

        Livewire::test(CreateOrdenServicio::class)
            ->fillForm([
                'vehiculo_id' => $vehiculo->id,
                'tipo' => 'particular',
                'kilometraje_ingreso' => 41500,
                'motivo_ingreso' => 'Ruido al frenar',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirectContains('/panel/checkin/');

        $orden = OrdenServicio::first();
        $this->assertMatchesRegularExpression('/^OS-\d{4}-000001$/', $orden->folio);
        $this->assertSame($this->asesor->id, $orden->asesor_id);
        $this->assertNotNull($orden->checkin);
        $this->assertSame(41500, $orden->checkin->kilometraje);
    }

    public function test_una_orden_de_seguro_exige_aseguradora(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        Livewire::test(CreateOrdenServicio::class)
            ->fillForm(['vehiculo_id' => $vehiculo->id, 'tipo' => 'seguro', 'motivo_ingreso' => 'Choque'])
            ->call('create')
            ->assertHasFormErrors(['aseguradora_id' => 'required']);

        Livewire::test(CreateOrdenServicio::class)
            ->fillForm([
                'vehiculo_id' => $vehiculo->id, 'tipo' => 'seguro', 'motivo_ingreso' => 'Choque',
                'aseguradora_id' => Aseguradora::firstWhere('nombre', 'Qualitas')->id, 'numero_siniestro' => '04-123456',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('04-123456', OrdenServicio::first()->numero_siniestro);
    }

    public function test_se_puede_recibir_desde_la_ficha_del_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 12345]);

        Livewire::withQueryParams(['vehiculo' => $vehiculo->id])
            ->test(CreateOrdenServicio::class)
            ->assertSchemaStateSet(['vehiculo_id' => $vehiculo->id, 'kilometraje_ingreso' => 12345]);
    }

    public function test_la_busqueda_encuentra_vehiculos_por_placas_y_dueno(): void
    {
        $vehiculo = Vehiculo::factory()->create(['placas' => 'NXT-55-12']);

        $porPlacas = OrdenServicioForm::buscarVehiculos('nxt 5512');
        $porDueno = OrdenServicioForm::buscarVehiculos($vehiculo->cliente->nombre);

        $this->assertArrayHasKey($vehiculo->id, $porPlacas);
        $this->assertArrayHasKey($vehiculo->id, $porDueno);
    }

    public function test_el_tecnico_registra_hallazgos_con_dtc_normalizados(): void
    {
        $orden = (new AbrirOrden)(Vehiculo::factory()->create(), TipoOrden::Particular, $this->asesor);

        Livewire::test(DiagnosticosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => ViewOrdenServicio::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'hallazgo' => 'Falla de encendido cilindro 1',
                'prioridad' => 'urgente',
                'codigos_dtc' => 'p0301 , p0301 p0420',
            ])
            ->assertHasNoActionErrors();

        $d = Diagnostico::first();
        $this->assertSame('P0301, P0420', $d->codigos_dtc);
        $this->assertSame($this->asesor->id, $d->user_id);
    }

    public function test_la_ficha_de_la_orden_se_muestra(): void
    {
        $orden = (new AbrirOrden)(Vehiculo::factory()->create(), TipoOrden::Particular, $this->asesor);

        $this->get("/panel/ordenes/{$orden->id}")->assertOk()->assertSee($orden->folio)->assertSee('Todavía no se hace el inventario');
    }
}
