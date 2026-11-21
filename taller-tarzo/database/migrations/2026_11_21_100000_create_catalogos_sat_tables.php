<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos del SAT necesarios para capturar datos fiscales (CFDI 4.0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_regimenes_fiscales', function (Blueprint $table) {
            $table->string('clave', 3)->primary();
            $table->string('descripcion');
            $table->boolean('persona_fisica')->default(false);
            $table->boolean('persona_moral')->default(false);
        });

        Schema::create('sat_usos_cfdi', function (Blueprint $table) {
            $table->string('clave', 4)->primary();
            $table->string('descripcion');
            $table->boolean('persona_fisica')->default(false);
            $table->boolean('persona_moral')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_usos_cfdi');
        Schema::dropIfExists('sat_regimenes_fiscales');
    }
};
