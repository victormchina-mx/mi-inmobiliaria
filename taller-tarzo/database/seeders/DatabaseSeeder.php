<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Datos base que el sistema necesita para funcionar. Seguro de repetir en producción.
 * Los usuarios se crean con: php artisan tarzo:crear-usuario
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesYPermisosSeeder::class,
            CatalogosSatSeeder::class,
            CatalogosTallerSeeder::class,
            CatalogosCotizacionSeeder::class,
        ]);
    }
}
