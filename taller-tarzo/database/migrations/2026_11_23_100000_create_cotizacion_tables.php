<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 2 · Cotización y valuación.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Clave estable para que el cotizador encuentre las áreas de laminado y pintura aunque se renombren.
        Schema::table('areas', function (Blueprint $table) {
            $table->string('clave', 30)->nullable()->unique()->after('id');
        });

        Schema::create('parametros', function (Blueprint $table) {
            $table->string('clave', 60)->primary();
            $table->decimal('valor', 12, 4)->default(0);
            $table->string('descripcion')->nullable();
            $table->string('unidad', 20)->nullable();
        });

        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->string('nombre', 160);
            $table->decimal('horas_estandar', 6, 2)->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('piezas_carroceria', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->decimal('superficie_m2', 6, 2)->default(0);
            $table->decimal('horas_laminado_ref', 5, 2)->nullable(); // referencia para daño medio
            $table->decimal('horas_pintura_ref', 5, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('paquetes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 160);
            $table->string('descripcion')->nullable();
            $table->json('partidas'); // plantilla de partidas
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tabuladores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aseguradora_id')->unique()->constrained('aseguradoras')->cascadeOnDelete();
            $table->decimal('descuento_refacciones', 5, 2)->default(0);
            $table->decimal('descuento_mano_obra', 5, 2)->default(0);
            $table->decimal('factor_materiales', 6, 3)->default(1); // multiplica el costo de materiales de pintura
            $table->text('conceptos_no_cubiertos')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('tabulador_tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tabulador_id')->constrained('tabuladores')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->decimal('tarifa_hora', 10, 2);
            $table->unique(['tabulador_id', 'area_id']);
        });

        Schema::create('siniestros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->unique()->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('aseguradora_id')->constrained('aseguradoras')->restrictOnDelete();
            $table->string('numero', 60)->nullable();
            $table->string('ajustador_nombre', 160)->nullable();
            $table->string('ajustador_telefono', 20)->nullable();
            $table->string('ajustador_email')->nullable();
            $table->string('deducible_tipo', 12)->default('monto'); // monto | porcentaje
            $table->decimal('deducible_valor', 12, 2)->default(0);
            $table->decimal('valor_comercial', 12, 2)->nullable(); // base del deducible en porcentaje
            $table->decimal('demerito', 12, 2)->default(0);
            $table->decimal('salvamento', 12, 2)->default(0);
            $table->string('orden_admision')->nullable(); // PDF del vale u orden de admisión
            $table->string('folio_autorizacion', 60)->nullable();
            $table->string('estatus', 30)->default('por_valuar'); // App\Enums\EstatusSiniestro
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('folio', 30)->unique();
            $table->string('estado', 20)->default('borrador'); // App\Enums\EstadoCotizacion
            $table->foreignId('aseguradora_id')->nullable()->constrained('aseguradoras')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('iva_tasa', 5, 4)->default(0.16);
            // Totales calculados (sin IVA salvo que se indique)
            $table->decimal('subtotal_mano_obra', 12, 2)->default(0);
            $table->decimal('subtotal_refacciones', 12, 2)->default(0);
            $table->decimal('subtotal_hojalateria_pintura', 12, 2)->default(0);
            $table->decimal('subtotal_otros', 12, 2)->default(0);
            $table->unsignedSmallInteger('ciclos_cabina')->default(0);
            $table->decimal('importe_cabina', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);           // con IVA
            $table->decimal('monto_aseguradora', 12, 2)->default(0); // con IVA, ya restados deducible, demérito y salvamento
            $table->decimal('monto_cliente', 12, 2)->default(0);     // con IVA, incluye deducible y demérito
            $table->decimal('deducible', 12, 2)->default(0);
            $table->unsignedSmallInteger('vigencia_dias')->default(15);
            $table->text('notas')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('respondida_at')->nullable();
            $table->timestamp('recordatorio_at')->nullable();
            $table->timestamps();
            $table->unique(['orden_servicio_id', 'version']);
        });

        Schema::create('aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->string('rol', 15);     // cliente | ajustador
            $table->string('canal', 15);   // enlace | asesor
            $table->string('nombre', 160);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // si la registró el asesor
            $table->string('ip', 45)->nullable();
            $table->string('dispositivo')->nullable();
            $table->string('huella', 64);  // sha256 de la cotización respondida
            $table->unsignedSmallInteger('partidas_aprobadas')->default(0);
            $table->unsignedSmallInteger('partidas_rechazadas')->default(0);
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('tipo', 20); // App\Enums\TipoPartida
            $table->string('descripcion');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('operacion_id')->nullable()->constrained('operaciones')->nullOnDelete();
            $table->foreignId('pieza_id')->nullable()->constrained('piezas_carroceria')->nullOnDelete();
            $table->foreignId('diagnostico_id')->nullable()->constrained('diagnosticos')->nullOnDelete();
            // Mano de obra
            $table->decimal('horas', 6, 2)->nullable();
            // Hojalatería y pintura
            $table->string('operacion_hyp', 20)->nullable(); // App\Enums\OperacionHyP
            $table->string('nivel_dano', 10)->nullable();    // App\Enums\NivelDano
            $table->string('tipo_pintura', 15)->nullable();  // App\Enums\TipoPintura
            $table->decimal('horas_laminado', 6, 2)->nullable();
            $table->decimal('horas_pintura', 6, 2)->nullable();
            $table->decimal('materiales_manual', 12, 2)->nullable(); // si el valuador ajusta el cálculo
            // Refacciones / otros
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('margen', 5, 2)->nullable();          // %
            $table->decimal('precio_manual', 12, 2)->nullable();  // precio unitario fijo
            $table->string('numero_parte', 60)->nullable();
            $table->string('proveedor', 120)->nullable();
            $table->string('disponibilidad', 15)->nullable(); // App\Enums\Disponibilidad
            $table->string('calidad', 15)->nullable();        // App\Enums\CalidadRefaccion
            // Quién paga y resultado del cálculo
            $table->string('paga', 15)->default('cliente');    // cliente | aseguradora
            $table->decimal('tarifa_aplicada', 10, 2)->nullable();
            $table->decimal('importe_materiales', 12, 2)->default(0);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('importe', 12, 2)->default(0);     // sin IVA
            // Autorización
            $table->string('estado', 12)->default('pendiente'); // App\Enums\EstadoPartida
            $table->foreignId('aprobacion_id')->nullable()->constrained('aprobaciones')->nullOnDelete();
            $table->string('comentario_respuesta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas');
        Schema::dropIfExists('aprobaciones');
        Schema::dropIfExists('cotizaciones');
        Schema::dropIfExists('siniestros');
        Schema::dropIfExists('tabulador_tarifas');
        Schema::dropIfExists('tabuladores');
        Schema::dropIfExists('paquetes');
        Schema::dropIfExists('piezas_carroceria');
        Schema::dropIfExists('operaciones');
        Schema::dropIfExists('parametros');
        Schema::table('areas', function (Blueprint $table) {
            $table->dropUnique(['clave']);
            $table->dropColumn('clave');
        });
    }
};
