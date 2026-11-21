<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 4 · Almacén y refacciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 160);
            $table->string('rfc', 13)->nullable()->index();
            $table->string('contacto', 160)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('dias_entrega')->default(2);
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('codigo_barras', 60)->nullable()->unique();
            $table->string('nombre', 200);
            $table->string('categoria', 20);   // App\Enums\CategoriaProducto
            $table->string('unidad', 10);      // App\Enums\UnidadMedida
            $table->string('ubicacion', 60)->nullable();
            $table->decimal('existencia', 12, 3)->default(0);
            $table->decimal('costo_promedio', 12, 4)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->decimal('stock_maximo', 12, 3)->default(0);
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->timestamp('ultimo_movimiento_at')->nullable();
            $table->timestamp('alerta_minimo_at')->nullable(); // se reinicia al reponer
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->string('estado', 20)->default('borrador'); // App\Enums\EstadoOrdenCompra
            $table->date('fecha_estimada')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('recibida_at')->nullable();
            $table->timestamp('alerta_retraso_at')->nullable();
            $table->timestamps();
        });

        Schema::create('requisiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->foreignId('partida_id')->nullable()->unique()->constrained('partidas')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion');
            $table->string('numero_parte', 60)->nullable();
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->string('surte', 15)->default('taller'); // taller | aseguradora
            $table->string('estado', 15)->default('pendiente'); // App\Enums\EstadoRequisicion
            $table->decimal('costo_real', 12, 2)->nullable(); // costo unitario según la factura del proveedor
            $table->timestamp('recibida_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orden_compra_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('requisicion_id')->nullable()->constrained('requisiciones')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion');
            $table->string('numero_parte', 60)->nullable();
            $table->decimal('cantidad', 10, 3);
            $table->decimal('cantidad_recibida', 10, 3)->default(0);
            $table->decimal('costo_unitario', 12, 4)->default(0); // estimado al pedir
            $table->decimal('costo_real', 12, 4)->nullable();    // de la factura
            $table->timestamps();
        });

        Schema::create('recepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('factura_xml')->nullable();
            $table->string('factura_uuid', 40)->nullable()->index();
            $table->decimal('factura_total', 12, 2)->nullable();
            $table->string('foto')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('tipo', 15); // App\Enums\TipoMovimiento
            $table->decimal('cantidad', 12, 3); // positiva: entra; negativa: sale
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('costo_total', 12, 2);
            $table->decimal('existencia_resultante', 12, 3);
            $table->decimal('costo_promedio_resultante', 12, 4);
            $table->foreignId('orden_servicio_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->foreignId('recepcion_id')->nullable()->constrained('recepciones')->nullOnDelete();
            $table->boolean('gasto_general')->default(false);
            $table->string('referencia')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['producto_id', 'created_at']);
        });

        // Costo real de la refacción según factura (lo usa la rentabilidad por orden del Módulo 6).
        Schema::table('partidas', function (Blueprint $table) {
            $table->decimal('costo_real', 12, 2)->nullable()->after('importe');
        });
    }

    public function down(): void
    {
        Schema::table('partidas', fn (Blueprint $table) => $table->dropColumn('costo_real'));
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('recepciones');
        Schema::dropIfExists('orden_compra_partidas');
        Schema::dropIfExists('requisiciones');
        Schema::dropIfExists('ordenes_compra');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('proveedores');
    }
};
