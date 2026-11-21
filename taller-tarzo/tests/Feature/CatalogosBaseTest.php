<?php

namespace Tests\Feature;

use App\Models\Etapa;
use App\Models\RegimenFiscal;
use App\Models\UsoCfdi;
use App\Support\MatrizPermisos;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogosBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_roles_permisos_y_catalogos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Role::count());
        $this->assertSame(count(MatrizPermisos::todos()), Permission::count());
        $this->assertSame(10, Etapa::count());
        $this->assertSame('recepcion', Etapa::ordenadas()->first()->clave);
        $this->assertTrue(Etapa::ordenadas()->get()->last()->es_final);
        $this->assertTrue(RegimenFiscal::find('626')->persona_moral);
        $this->assertFalse(UsoCfdi::find('D01')->persona_moral);
    }

    public function test_el_seeder_se_puede_repetir_sin_duplicar(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(10, Etapa::count());
        $this->assertSame(7, Role::count());
    }

    public function test_la_matriz_solo_usa_permisos_definidos(): void
    {
        $definidos = MatrizPermisos::todos();

        foreach (MatrizPermisos::porRol() as $rol => $permisos) {
            $this->assertSame([], array_diff($permisos, $definidos), "El rol {$rol} usa permisos no definidos");
        }
    }
}
