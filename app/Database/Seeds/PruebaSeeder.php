<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PruebaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'nombre'    => 'Conexión Exitosa desde Hostinger',
            'creado_en' => date('Y-m-d H:i:s'),
        ];

        // Inserta el registro en la tabla que creamos antes
        $this->db->table('tabla_prueba')->insert($data);
    }
}
