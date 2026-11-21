<?php

namespace Tests\Feature;

use App\Actions\Ordenes\AbrirOrden;
use App\Actions\Ordenes\CambiarEtapa;
use App\Enums\Rol;
use App\Enums\TipoOrden;
use App\Models\Etapa;
use App\Models\User;
use App\Models\Vehiculo;
use App\Support\GeneradorFolio;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenesServicioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function conRol(Rol $rol): User
    {
        return tap(User::factory()->create())->assignRole($rol->value);
    }

    public function test_los_folios_son_consecutivos_y_reinician_por_anio(): void
    {
        $this->assertSame('OS-2027-000001', GeneradorFolio::siguiente('OS', 2027));
        $this->assertSame('OS-2027-000002', GeneradorFolio::siguiente('OS', 2027));
        $this->assertSame('OS-2028-000001', GeneradorFolio::siguiente('OS', 2028));
        $this->assertSame('CO-2027-000001', GeneradorFolio::siguiente('CO', 2027));
    }

    public function test_abrir_orden_la_deja_en_recepcion_con_historial(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        $asesor = $this->conRol(Rol::Asesor);

        $orden = (new AbrirOrden)($vehiculo, TipoOrden::Seguro, $asesor, [
            'kilometraje_ingreso' => 51200,
            'motivo_ingreso' => 'Golpe en facia delantera',
        ]);

        $this->assertMatchesRegularExpression('/^OS-\d{4}-000001$/', $orden->folio);
        $this->assertSame(Etapa::RECEPCION, $orden->etapa->clave);
        $this->assertSame($vehiculo->cliente_id, $orden->cliente_id);
        $this->assertSame(1, $orden->historialEtapas()->count());
        $this->assertSame(51200, $vehiculo->fresh()->kilometraje);
    }

    public function test_el_jefe_de_taller_mueve_la_orden_y_queda_registro(): void
    {
        $orden = (new AbrirOrden)(Vehiculo::factory()->create(), TipoOrden::Particular);
        $jefe = $this->conRol(Rol::JefeTaller);
        $diagnostico = Etapa::where('clave', 'diagnostico')->first();

        (new CambiarEtapa)($orden, $diagnostico, $jefe, 'Pasa a revisión');

        $this->assertSame($diagnostico->id, $orden->fresh()->etapa_id);
        $ultimo = $orden->historialEtapas()->first();
        $this->assertSame($jefe->id, $ultimo->user_id);
        $this->assertSame('Pasa a revisión', $ultimo->comentario);
        $this->assertFalse($ultimo->excepcion);
    }

    public function test_el_tecnico_no_puede_mover_ordenes(): void
    {
        $orden = (new AbrirOrden)(Vehiculo::factory()->create(), TipoOrden::Particular);

        $this->expectException(AuthorizationException::class);

        (new CambiarEtapa)($orden, Etapa::where('clave', 'mecanica')->first(), $this->conRol(Rol::Tecnico));
    }

    public function test_el_asesor_no_puede_forzar_reglas(): void
    {
        $orden = (new AbrirOrden)(Vehiculo::factory()->create(), TipoOrden::Particular);

        $this->expectException(AuthorizationException::class);

        (new CambiarEtapa)($orden, Etapa::where('clave', 'mecanica')->first(), $this->conRol(Rol::Asesor), forzar: true);
    }

    public function test_placas_y_vin_se_normalizan(): void
    {
        $vehiculo = Vehiculo::factory()->create(['placas' => 'mxa-12-34', 'vin' => '1hgcm8263 3a004352']);

        $this->assertSame('MXA1234', $vehiculo->placas);
        $this->assertSame('1HGCM82633A004352', $vehiculo->vin);
        $this->assertTrue(Vehiculo::buscar('mxa 1234')->whereKey($vehiculo->id)->exists());
    }
}
