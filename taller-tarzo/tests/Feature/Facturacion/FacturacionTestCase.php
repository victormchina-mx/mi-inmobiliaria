<?php

namespace Tests\Feature\Facturacion;

use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Actions\Facturacion\PrepararFacturas;
use App\Enums\Rol;
use App\Enums\TipoOrden;
use App\Models\Aseguradora;
use App\Models\DatosFiscales;
use App\Models\Factura;
use App\Models\OrdenServicio;
use App\Models\Siniestro;
use App\Models\User;
use App\Services\Cfdi\Facturador;
use Tests\Feature\Piso\PisoTestCase;

abstract class FacturacionTestCase extends PisoTestCase
{
    protected User $contador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contador = $this->usuario(Rol::Contabilidad);
        Aseguradora::where('nombre', 'Qualitas')->update([
            'rfc' => 'QCS931209G49', 'razon_social' => 'QUALITAS COMPAÑIA DE SEGUROS', 'regimen_fiscal' => '601', 'codigo_postal' => '01210', 'dias_pago' => 30,
        ]);
    }

    protected function conDatosFiscales(OrdenServicio $orden): OrdenServicio
    {
        DatosFiscales::create([
            'cliente_id' => $orden->cliente_id, 'rfc' => 'GODE561231GR8', 'nombre_fiscal' => 'EMILIO GONZALEZ DOMINGUEZ',
            'regimen_fiscal' => '612', 'codigo_postal' => '50000', 'uso_cfdi' => 'G03',
        ]);

        return $orden->fresh();
    }

    /** Orden particular aprobada (2,300 + IVA) con datos fiscales. */
    protected function ordenParticular(): OrdenServicio
    {
        return $this->conDatosFiscales($this->ordenAprobada());
    }

    /** Orden de seguro con deducible de $500, todas las partidas a cargo de la aseguradora. */
    protected function ordenSeguro(float $deducible = 500): OrdenServicio
    {
        $orden = $this->orden(TipoOrden::Seguro);
        Siniestro::create(['orden_servicio_id' => $orden->id, 'aseguradora_id' => $orden->aseguradora_id, 'numero' => 'S-2027-1', 'deducible_tipo' => 'monto', 'deducible_valor' => $deducible]);
        $c = $this->cotizacionConPartidas($orden, 'aseguradora');
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'ajustador', [], 'Ajustador');

        return $this->conDatosFiscales($orden->fresh());
    }

    protected function facturaTimbrada(?OrdenServicio $orden = null): Factura
    {
        $f = app(PrepararFacturas::class)($orden ?? $this->ordenParticular(), $this->contador)->first();

        return app(Facturador::class)->timbrar($f)->refresh();
    }
}
