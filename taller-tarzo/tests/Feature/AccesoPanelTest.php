<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function usuario(?Rol $rol, bool $activo = true): User
    {
        $usuario = User::factory()->create(['activo' => $activo]);

        if ($rol) {
            $usuario->assignRole($rol->value);
        }

        return $usuario;
    }

    public function test_la_raiz_redirige_al_panel(): void
    {
        $this->get('/')->assertRedirect('/panel');
    }

    public function test_un_visitante_va_al_login(): void
    {
        $this->get('/panel')->assertRedirect('/panel/login');
        $this->get('/panel/login')->assertOk()->assertSee('TARZO');
    }

    public function test_usuario_sin_rol_no_entra(): void
    {
        $this->actingAs($this->usuario(null))->get('/panel')->assertForbidden();
    }

    public function test_usuario_inactivo_no_entra(): void
    {
        $this->actingAs($this->usuario(Rol::Dueno, activo: false))->get('/panel')->assertForbidden();
    }

    public static function pantallasCatalogo(): array
    {
        return [['areas'], ['bahias'], ['tecnicos'], ['etapas'], ['aseguradoras'], ['users']];
    }

    #[DataProvider('pantallasCatalogo')]
    public function test_el_dueno_ve_y_crea_en_cada_catalogo(string $ruta): void
    {
        $dueno = $this->usuario(Rol::Dueno);

        $this->actingAs($dueno)->get("/panel/{$ruta}")->assertOk();
        $this->actingAs($dueno)->get("/panel/{$ruta}/create")->assertOk();
    }

    public function test_el_asesor_no_administra_usuarios_ni_catalogos(): void
    {
        $asesor = $this->usuario(Rol::Asesor);

        $this->actingAs($asesor)->get('/panel')->assertOk();
        $this->actingAs($asesor)->get('/panel/users')->assertForbidden();
        $this->actingAs($asesor)->get('/panel/areas')->assertForbidden();
    }

    public function test_el_tecnico_solo_tiene_permisos_de_piso(): void
    {
        $tecnico = $this->usuario(Rol::Tecnico);

        $this->assertTrue($tecnico->can('piso.tareas_propias'));
        $this->assertFalse($tecnico->can('piso.gestionar'));
        $this->assertFalse($tecnico->can('facturacion.ver'));
    }

    public function test_el_login_actualiza_ultimo_acceso(): void
    {
        $dueno = $this->usuario(Rol::Dueno);
        $this->assertNull($dueno->ultimo_acceso_at);

        auth()->login($dueno);

        $this->assertNotNull($dueno->fresh()->ultimo_acceso_at);
    }
}
