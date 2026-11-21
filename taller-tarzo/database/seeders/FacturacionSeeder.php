<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

/**
 * Módulo 6: parámetros de rentabilidad. No pisa valores existentes.
 */
class FacturacionSeeder extends Seeder
{
    public function run(): void
    {
        Parametro::firstOrCreate(['clave' => 'margen_minimo'], [
            'valor' => 25, 'descripcion' => 'Margen mínimo por orden; por debajo se avisa a la gerencia', 'unidad' => '%',
        ]);
    }
}
