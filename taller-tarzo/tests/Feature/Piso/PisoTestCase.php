<?php

namespace Tests\Feature\Piso;

use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Enums\Rol;
use App\Enums\TipoOrden;
use App\Models\Area;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Models\Tecnico;
use App\Models\User;
use Tests\Feature\Cotizacion\CotizacionTestCase;

abstract class PisoTestCase extends CotizacionTestCase
{
    protected User $jefe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jefe = $this->usuario(Rol::JefeTaller);
    }

    protected function etapa(string $clave): Etapa
    {
        return Etapa::where('clave', $clave)->firstOrFail();
    }

    /**
     * Orden con cotización aprobada (mano de obra 2 h + refacción).
     */
    protected function ordenAprobada(TipoOrden $tipo = TipoOrden::Particular): OrdenServicio
    {
        $orden = $this->orden($tipo);
        $c = $this->cotizacionConPartidas($orden, $tipo === TipoOrden::Seguro ? 'aseguradora' : 'cliente');
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, $tipo === TipoOrden::Seguro ? 'ajustador' : 'cliente', [], 'Laura');

        return $orden->fresh();
    }

    protected function tecnicoConUsuario(string $areaClave = 'mecanica'): Tecnico
    {
        $usuario = $this->usuario(Rol::Tecnico);

        return Tecnico::create([
            'user_id' => $usuario->id,
            'area_id' => Area::where('clave', $areaClave)->value('id'),
            'nombre' => 'Diego Sánchez',
            'costo_hora' => 120,
            'capacidad_horas_dia' => 8,
        ]);
    }
}
