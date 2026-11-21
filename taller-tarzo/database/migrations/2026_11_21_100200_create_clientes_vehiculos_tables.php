<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes, flotillas, datos fiscales, contactos, vehículos y pólizas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flotillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 160);
            $table->unsignedSmallInteger('dias_credito')->default(0);
            $table->decimal('limite_credito', 12, 2)->default(0);
            $table->decimal('descuento_mano_obra', 5, 2)->default(0);
            $table->decimal('descuento_refacciones', 5, 2)->default(0);
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_persona', 10)->default('fisica'); // App\Enums\TipoPersona
            $table->string('nombre', 160);
            $table->string('apellidos', 160)->nullable();
            $table->string('telefono', 20)->nullable()->index();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('calle')->nullable();
            $table->string('colonia', 120)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('estado', 80)->nullable();
            $table->string('codigo_postal', 5)->nullable();
            $table->string('canal_preferido', 20)->default('whatsapp'); // App\Enums\CanalPreferido
            $table->boolean('acepta_promociones')->default(false);
            $table->foreignId('flotilla_id')->nullable()->constrained('flotillas')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['nombre', 'apellidos']);
        });

        Schema::create('datos_fiscales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->cascadeOnDelete();
            $table->string('rfc', 13)->index();
            $table->string('nombre_fiscal');
            $table->string('regimen_fiscal', 3);
            $table->string('codigo_postal', 5);
            $table->string('uso_cfdi', 4)->default('G03');
            $table->string('email_facturacion')->nullable();
            $table->string('constancia_path')->nullable();
            $table->timestamps();
            $table->foreign('regimen_fiscal')->references('clave')->on('sat_regimenes_fiscales');
            $table->foreign('uso_cfdi')->references('clave')->on('sat_usos_cfdi');
        });

        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('flotilla_id')->nullable()->constrained('flotillas')->cascadeOnDelete();
            $table->string('nombre', 160);
            $table->string('puesto', 120)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('puede_aprobar')->default(false);
            $table->timestamps();
        });

        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('vin', 17)->nullable()->unique();
            $table->string('placas', 12)->nullable()->index();
            $table->string('marca', 60);
            $table->string('modelo', 80);
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('version', 80)->nullable();
            $table->string('color', 40)->nullable();
            $table->string('motor', 40)->nullable();
            $table->string('transmision', 20)->nullable();
            $table->unsignedInteger('kilometraje')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('polizas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('aseguradora_id')->constrained('aseguradoras')->restrictOnDelete();
            $table->string('numero', 60);
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->string('cobertura', 20)->nullable(); // amplia, limitada, rc
            $table->decimal('deducible_porcentaje', 5, 2)->nullable();
            $table->decimal('deducible_monto', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['aseguradora_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polizas');
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('contactos');
        Schema::dropIfExists('datos_fiscales');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('flotillas');
    }
};
