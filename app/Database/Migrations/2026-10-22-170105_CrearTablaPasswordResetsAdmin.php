<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaPasswordResetsAdmin extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'aemail' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'used' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('token');
        $this->forge->createTable('admin_password_resets');
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_password_resets');
    }
}
