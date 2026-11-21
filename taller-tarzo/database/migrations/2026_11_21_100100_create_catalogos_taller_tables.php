<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos operativos del taller: áreas, bahías, técnicos, etapas del tablero,
 * aseguradoras y consecutivos para folios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('descripcion')->nullable();
            $table->decimal('tarifa_hora', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('bahias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('nombre', 80)->unique();
            $table->string('tipo', 20)->default('bahia'); // App\Enums\TipoBahia
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('nombre', 120);
            $table->string('especialidad', 120)->nullable();
            $table->decimal('costo_hora', 10, 2)->default(0);
            $table->decimal('capacidad_horas_dia', 4, 1)->default(8);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('etapas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre', 80);
            $table->unsignedSmallInteger('orden');
            $table->string('color', 20)->default('gray');
            $table->unsignedSmallInteger('dias_max')->nullable();
            $table->unsignedSmallInteger('limite_autos')->nullable();
            $table->boolean('notifica_cliente')->default(false);
            $table->boolean('es_final')->default(false);
            $table->timestamps();
        });

        Schema::create('aseguradoras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('rfc', 13)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('dias_pago')->default(30);
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('consecutivos', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 10);
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('ultimo')->default(0);
            $table->unique(['serie', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consecutivos');
        Schema::dropIfExists('aseguradoras');
        Schema::dropIfExists('etapas');
        Schema::dropIfExists('tecnicos');
        Schema::dropIfExists('bahias');
        Schema::dropIfExists('areas');
    }
};
