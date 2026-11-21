<?php

namespace Database\Seeders;

use App\Support\MatrizPermisos;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea o sincroniza roles y permisos según App\Support\MatrizPermisos.
 * Es seguro ejecutarlo en producción cada vez que cambie la matriz.
 */
class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (MatrizPermisos::todos() as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (MatrizPermisos::porRol() as $rol => $permisos) {
            Role::findOrCreate($rol, 'web')->syncPermissions($permisos);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
