<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 1 · Recepción y diagnóstico: check-in del vehículo, daños previos y diagnóstico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->unique()->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('nivel_combustible')->nullable(); // octavos: 0 a 8
            $table->unsignedInteger('kilometraje')->nullable();
            $table->string('foto_odometro')->nullable();
            $table->json('fotos')->nullable();          // vistas generales del vehículo
            $table->json('testigos')->nullable();       // App\Enums\Testigo[]
            $table->json('inventario')->nullable();     // App\Enums\ElementoInventario[]
            $table->text('pertenencias')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('video')->nullable();
            // Firma del cliente (en tableta o por enlace)
            $table->string('firma')->nullable();
            $table->string('firmado_por', 160)->nullable();
            $table->timestamp('firmado_at')->nullable();
            $table->string('firma_ip', 45)->nullable();
            $table->string('firma_dispositivo')->nullable();
            $table->string('firma_medio', 20)->nullable(); // tableta | enlace
            $table->string('firma_huella', 64)->nullable(); // sha256 del contenido firmado
            $table->boolean('acepta_aviso_privacidad')->default(false);
            $table->boolean('acepta_condiciones')->default(false);
            $table->string('pdf')->nullable();
            $table->timestamp('pdf_generado_at')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('danos_previos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_id')->constrained('checkins')->cascadeOnDelete();
            $table->string('zona', 40);  // App\Enums\ZonaVehiculo
            $table->string('tipo', 20);  // App\Enums\TipoDano
            $table->string('descripcion')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hallazgo');
            $table->text('detalle')->nullable();
            $table->string('codigos_dtc')->nullable(); // p. ej. "P0301, P0420"
            $table->string('prioridad', 20)->default('recomendado'); // App\Enums\Prioridad
            $table->json('fotos')->nullable();
            $table->boolean('convertido_a_partida')->default(false); // lo usa el Módulo 2
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
        Schema::dropIfExists('danos_previos');
        Schema::dropIfExists('checkins');
    }
};
