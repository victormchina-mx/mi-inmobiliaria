<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaPrueba extends Migration
{
    public function up()
    {
        // Definir los campos de una tabla de prueba
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'creado_en' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        // Definir la llave primaria y crear la tabla
        $this->forge->addKey('id', true);
        $this->forge->createTable('tabla_prueba');
    }

    public function down()
    {
        // Eliminar la tabla si se revierte la migración
        $this->forge->dropTable('tabla_prueba');
    }
}
