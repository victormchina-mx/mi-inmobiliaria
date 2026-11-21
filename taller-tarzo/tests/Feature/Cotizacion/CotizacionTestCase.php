<?php

namespace Tests\Feature\Cotizacion;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Actions\Ordenes\AbrirOrden;
use App\Enums\TipoOrden;
use App\Models\Area;
use App\Models\Aseguradora;
use App\Models\Cotizacion;
use App\Models\OrdenServicio;
use App\Models\Parametro;
use App\Models\PiezaCarroceria;
use App\Models\Vehiculo;
use App\Services\Cotizacion\Cotizador;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Recepcion\RecepcionTestCase;

abstract class CotizacionTestCase extends RecepcionTestCase
{
    protected Area $mecanica;

    protected function setUp(): void
    {
        parent::setUp();

        // Tarifas conocidas para poder verificar montos.
        Area::where('clave', 'mecanica')->update(['tarifa_hora' => 500]);
        Area::where('clave', 'hojalateria')->update(['tarifa_hora' => 450]);
        Area::where('clave', 'pintura')->update(['tarifa_hora' => 480]);
        Parametro::whereKey('costo_m2_solida')->update(['valor' => 400]);
        Parametro::whereKey('tarifa_ciclo_cabina')->update(['valor' => 800]);
        Parametro::whereKey('margen_refacciones')->update(['valor' => 30]);
        Cache::forget('parametros');

        $this->mecanica = Area::firstWhere('clave', 'mecanica');
    }

    protected function orden(TipoOrden $tipo = TipoOrden::Particular): OrdenServicio
    {
        $vehiculo = Vehiculo::factory()->create();
        $vehiculo->cliente->update(['email' => 'cliente@example.com', 'telefono' => '7221234567', 'whatsapp' => null]);

        return (new AbrirOrden)($vehiculo, $tipo, $this->asesor, [
            'motivo_ingreso' => 'Prueba',
            'aseguradora_id' => $tipo === TipoOrden::Seguro ? Aseguradora::firstWhere('nombre', 'Qualitas')->id : null,
        ]);
    }

    /**
     * Cotización con mano de obra (2 h × 500 = 1,000) y refacción (1,000 × 1.3 = 1,300).
     */
    protected function cotizacionConPartidas(?OrdenServicio $orden = null, string $paga = 'cliente'): Cotizacion
    {
        $orden ??= $this->orden();
        $c = app(AbrirCotizacion::class)($orden, $this->asesor);
        $c->partidas()->create(['tipo' => 'mano_obra', 'descripcion' => 'Cambio de balatas', 'area_id' => $this->mecanica->id, 'horas' => 2, 'paga' => $paga, 'orden' => 1]);
        $c->partidas()->create(['tipo' => 'refaccion', 'descripcion' => 'Balatas delanteras', 'cantidad' => 1, 'costo_unitario' => 1000, 'paga' => $paga, 'orden' => 2]);

        return app(Cotizador::class)->recalcular($c);
    }

    protected function pieza(): PiezaCarroceria
    {
        return PiezaCarroceria::firstWhere('nombre', 'Facia delantera');
    }
}
