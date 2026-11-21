<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La orden de servicio es el centro del sistema: todo lo demás cuelga de ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->string('tipo', 20)->default('particular'); // App\Enums\TipoOrden
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
            $table->foreignId('aseguradora_id')->nullable()->constrained('aseguradoras')->nullOnDelete();
            $table->foreignId('poliza_id')->nullable()->constrained('polizas')->nullOnDelete();
            $table->string('numero_siniestro', 60)->nullable()->index();
            $table->unsignedInteger('kilometraje_ingreso')->nullable();
            $table->text('motivo_ingreso')->nullable();
            $table->timestamp('fecha_ingreso');
            $table->timestamp('etapa_desde');
            $table->timestamp('fecha_promesa')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['etapa_id', 'etapa_desde']);
        });

        Schema::create('historial_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('etapa_anterior_id')->nullable()->constrained('etapas')->nullOnDelete();
            $table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comentario')->nullable();
            $table->boolean('excepcion')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_etapas');
        Schema::dropIfExists('ordenes_servicio');
    }
};
