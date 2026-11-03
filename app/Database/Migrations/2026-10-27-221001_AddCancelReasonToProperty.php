<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCancelReasonToProperty extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('property', [
            'cancel_reason' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'status',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('property', 'cancel_reason');
    }
}
