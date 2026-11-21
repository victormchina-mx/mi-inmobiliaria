<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 3 · Control de operaciones y piso.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Reglas configurables por etapa del tablero.
        Schema::table('etapas', function (Blueprint $table) {
            $table->boolean('requiere_cotizacion_aprobada')->default(false)->after('es_final');
            $table->string('requiere_checklist', 15)->nullable()->after('requiere_cotizacion_aprobada'); // calidad | entrega
            $table->boolean('requiere_evidencia')->default(false)->after('requiere_checklist'); // para salir de la etapa (órdenes de seguro)
        });

        // Reglas por defecto para bases ya existentes (en instalaciones nuevas las pone el seeder).
        DB::table('etapas')->whereIn('clave', ['mecanica', 'hojalateria', 'pintura', 'armado', 'calidad'])->update(['requiere_cotizacion_aprobada' => true]);
        DB::table('etapas')->whereIn('clave', ['hojalateria', 'pintura'])->update(['requiere_evidencia' => true]);
        DB::table('etapas')->where('clave', 'listo_entrega')->update(['requiere_checklist' => 'calidad']);
        DB::table('etapas')->where('clave', 'entregado')->update(['requiere_checklist' => 'entrega']);

        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('partida_id')->nullable()->constrained('partidas')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->foreignId('bahia_id')->nullable()->constrained('bahias')->nullOnDelete();
            $table->string('descripcion');
            $table->decimal('horas_estandar', 6, 2)->default(0);
            $table->decimal('horas_reales', 7, 2)->default(0);
            $table->string('estado', 15)->default('pendiente'); // App\Enums\EstadoTarea
            $table->date('programada_para')->nullable();
            $table->unsignedSmallInteger('posicion')->default(0);
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('terminada_at')->nullable();
            $table->timestamps();
            $table->index(['tecnico_id', 'estado']);
            $table->unique(['partida_id', 'area_id']);
        });

        Schema::create('registros_tiempo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
            $table->unsignedInteger('minutos')->default(0);
            $table->string('motivo_pausa', 30)->nullable(); // App\Enums\MotivoPausa
            $table->timestamps();
        });

        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('etapa_id')->nullable()->constrained('etapas')->nullOnDelete();
            $table->foreignId('tarea_id')->nullable()->constrained('tareas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('momento', 20); // App\Enums\MomentoEvidencia
            $table->string('descripcion')->nullable();
            $table->string('archivo');
            $table->boolean('visible_cliente')->default(false);
            $table->timestamp('marcada_at')->nullable(); // marca de agua aplicada
            $table->timestamps();
        });

        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->string('tipo', 15); // calidad | entrega
            $table->json('items');
            $table->boolean('aprobado')->default(false);
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('kilometraje')->nullable();
            $table->string('firma')->nullable();
            $table->string('firmado_por', 160)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklists');
        Schema::dropIfExists('evidencias');
        Schema::dropIfExists('registros_tiempo');
        Schema::dropIfExists('tareas');
        Schema::table('etapas', function (Blueprint $table) {
            $table->dropColumn(['requiere_cotizacion_aprobada', 'requiere_checklist', 'requiere_evidencia']);
        });
    }
};
