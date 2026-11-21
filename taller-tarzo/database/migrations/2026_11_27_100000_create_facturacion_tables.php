<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 6: facturación CFDI 4.0, caja, cobranza y rentabilidad por orden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aseguradoras', function (Blueprint $table) {
            $table->string('razon_social')->nullable()->after('rfc');
            $table->string('regimen_fiscal', 3)->nullable()->after('razon_social');
            $table->string('codigo_postal', 5)->nullable()->after('regimen_fiscal');
            $table->string('uso_cfdi', 4)->default('G03')->after('codigo_postal');
            $table->string('email_facturacion')->nullable()->after('email');
        });

        // Costo de hora-hombre = (sueldo mensual × factor de cargas) ÷ horas productivas del mes.
        Schema::table('tecnicos', function (Blueprint $table) {
            $table->decimal('sueldo_mensual', 10, 2)->nullable()->after('costo_hora');
            $table->decimal('factor_cargas', 4, 2)->default(1.35)->after('sueldo_mensual');
            $table->unsignedSmallInteger('horas_productivas_mes')->default(192)->after('factor_cargas');
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('aseguradora_id')->nullable()->constrained('aseguradoras')->nullOnDelete();
            $table->foreignId('orden_servicio_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->timestamp('fecha');
            $table->string('forma_pago', 2);          // catálogo SAT c_FormaPago
            $table->decimal('monto', 12, 2);
            $table->decimal('por_aplicar', 12, 2);    // lo que falta asignar a facturas
            $table->boolean('es_anticipo')->default(false);
            $table->string('referencia', 120)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelado_at')->nullable();
            $table->string('motivo_cancelacion')->nullable();
            $table->timestamps();
            $table->index(['cliente_id', 'fecha']);
        });

        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 1)->default('I');  // I ingreso, E egreso (nota de crédito), P pago (REP)
            $table->string('serie', 10);
            $table->unsignedInteger('folio');
            $table->foreignId('orden_servicio_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->string('receptor_tipo', 15);      // cliente | aseguradora
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('aseguradora_id')->nullable()->constrained('aseguradoras')->nullOnDelete();
            $table->string('receptor_rfc', 13);
            $table->string('receptor_nombre');
            $table->string('receptor_regimen', 3);
            $table->string('receptor_cp', 5);
            $table->string('uso_cfdi', 4);
            $table->string('email')->nullable();
            $table->string('metodo_pago', 3)->nullable(); // PUE | PPD
            $table->string('forma_pago', 2)->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);  // por cobrar (solo ingresos)
            $table->string('estado', 15)->default('borrador'); // App\Enums\EstadoFactura
            $table->string('uuid', 36)->nullable()->unique();
            $table->string('pac_id', 60)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('relacion_tipo', 2)->nullable(); // c_TipoRelacion: 01 nota de crédito, 04 sustitución
            $table->foreignId('factura_relacionada_id')->nullable()->constrained('facturas')->nullOnDelete();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->nullOnDelete(); // REP
            $table->date('vence')->nullable();            // crédito
            $table->string('contrarrecibo', 60)->nullable();
            $table->date('fecha_pago_compromiso')->nullable();
            $table->text('notas')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('timbrada_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->string('motivo_cancelacion', 2)->nullable();
            $table->string('estado_cancelacion', 30)->nullable(); // respuesta del SAT (p. ej. en proceso)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['serie', 'folio']);
            $table->index(['estado', 'receptor_tipo']);
        });

        Schema::create('conceptos_factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->foreignId('partida_id')->nullable()->constrained('partidas')->nullOnDelete();
            $table->string('clave_prod_serv', 8);
            $table->string('clave_unidad', 3);
            $table->string('unidad', 30);
            $table->string('no_identificacion', 60)->nullable();
            $table->string('descripcion', 1000);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('valor_unitario', 14, 4);
            $table->decimal('importe', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('iva_tasa', 6, 4)->default(0.16);
            $table->decimal('iva', 12, 2)->default(0);
        });

        Schema::create('aplicaciones_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->foreignId('factura_id')->constrained('facturas')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->unsignedSmallInteger('parcialidad')->default(1);
            $table->timestamps();
        });

        // Subcontratos y gastos directos de la orden (grúa, rectificadora, cristales…).
        Schema::create('gastos_orden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->string('concepto', 160);
            $table->string('proveedor', 160)->nullable();
            $table->decimal('monto', 12, 2); // sin IVA
            $table->string('factura_proveedor', 60)->nullable();
            $table->date('fecha');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Foto de la utilidad al cerrar la orden (se puede recalcular si llegan facturas o costos después).
        Schema::create('cierres_orden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->unique()->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->decimal('ingreso', 12, 2);
            $table->boolean('ingreso_estimado')->default(false); // sin facturas: se usó la cotización
            $table->decimal('costo_refacciones', 12, 2);
            $table->decimal('costo_materiales', 12, 2);
            $table->decimal('costo_mano_obra', 12, 2);
            $table->decimal('costo_subcontratos', 12, 2);
            $table->decimal('utilidad', 12, 2);
            $table->decimal('margen', 6, 2)->nullable();
            $table->decimal('horas_reales', 8, 2)->default(0);
            $table->decimal('horas_estandar', 8, 2)->default(0);
            $table->timestamp('calculado_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_orden');
        Schema::dropIfExists('gastos_orden');
        Schema::dropIfExists('aplicaciones_pago');
        Schema::dropIfExists('conceptos_factura');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('pagos');
        Schema::table('tecnicos', fn (Blueprint $t) => $t->dropColumn(['sueldo_mensual', 'factor_cargas', 'horas_productivas_mes']));
        Schema::table('aseguradoras', fn (Blueprint $t) => $t->dropColumn(['razon_social', 'regimen_fiscal', 'codigo_postal', 'uso_cfdi', 'email_facturacion']));
    }
};
