<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 5 · Comunicación y seguimiento al cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Consentimiento para "finalidades adicionales" (recordatorios y encuestas), separado de promociones.
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('acepta_recordatorios')->default(true)->after('acepta_promociones');
        });

        Schema::create('plantillas_mensaje', function (Blueprint $table) {
            $table->id();
            $table->string('evento', 40);  // App\Enums\EventoMensaje
            $table->string('canal', 15);   // whatsapp | correo
            $table->string('asunto')->nullable();      // correo
            $table->text('cuerpo');                     // con variables {cliente}, {vehiculo}, {folio}…
            $table->string('plantilla_meta', 80)->nullable(); // nombre aprobado en Meta (WhatsApp)
            $table->string('idioma', 10)->default('es_MX');
            $table->json('variables_meta')->nullable(); // orden de {{1}}, {{2}}… en la plantilla de Meta
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['evento', 'canal']);
        });

        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('direccion', 10)->default('saliente'); // saliente | entrante
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('orden_servicio_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->string('evento', 40)->nullable();
            $table->string('canal', 15);
            $table->string('destino', 160)->nullable(); // teléfono o correo
            $table->string('asunto')->nullable();
            $table->text('cuerpo');
            $table->string('estado', 15)->default('pendiente'); // App\Enums\EstadoMensaje
            $table->json('meta')->nullable(); // plantilla de Meta, idioma y parámetros (para reintentar)
            $table->string('proveedor_id', 120)->nullable()->index(); // id de WhatsApp (wamid)
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // si lo escribió un asesor
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();
            $table->index(['cliente_id', 'created_at']);
        });

        Schema::create('reglas_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->unsignedInteger('cada_km')->nullable();
            $table->unsignedSmallInteger('cada_meses')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('regla_mantenimiento_id')->nullable()->constrained('reglas_mantenimiento')->nullOnDelete();
            $table->string('tipo', 15); // servicio | verificacion
            $table->string('titulo');
            $table->date('fecha');
            $table->unsignedInteger('km_estimado')->nullable();
            $table->string('referencia', 60); // evita duplicados (p. ej. "verif-2027-1" o "regla-3-orden-12")
            $table->string('estado', 15)->default('programado'); // programado | enviado | atendido | cancelado
            $table->foreignId('mensaje_id')->nullable()->constrained('mensajes')->nullOnDelete();
            $table->timestamps();
            $table->unique(['vehiculo_id', 'referencia']);
        });

        Schema::create('garantias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('tipo', 15); // App\Enums\TipoGarantia
            $table->string('descripcion');
            $table->date('inicia');
            $table->date('vence');
            $table->unsignedInteger('km_inicial')->nullable();
            $table->unsignedInteger('km_limite')->nullable();
            $table->string('estado', 15)->default('vigente'); // vigente | reclamada | vencida
            $table->foreignId('orden_reclamo_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->timestamp('alerta_at')->nullable();
            $table->timestamps();
        });

        Schema::create('encuestas_nps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->unique()->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('mensaje_id')->nullable()->constrained('mensajes')->nullOnDelete();
            $table->unsignedTinyInteger('calificacion')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('respondida_at')->nullable();
            $table->foreignId('seguimiento_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('seguimiento_notas')->nullable();
            $table->timestamp('seguimiento_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas_nps');
        Schema::dropIfExists('garantias');
        Schema::dropIfExists('recordatorios');
        Schema::dropIfExists('reglas_mantenimiento');
        Schema::dropIfExists('mensajes');
        Schema::dropIfExists('plantillas_mensaje');
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn('acepta_recordatorios'));
    }
};
