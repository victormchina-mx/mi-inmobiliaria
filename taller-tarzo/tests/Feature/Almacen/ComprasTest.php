<?php

namespace Tests\Feature\Almacen;

use App\Actions\Almacen\CambiarEstadoCompra;
use App\Actions\Almacen\CrearOrdenCompra;
use App\Actions\Almacen\RecibirMercancia;
use App\Actions\Cotizacion\CrearSuplemento;
use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Enums\EstadoOrdenCompra;
use App\Enums\EstadoRequisicion;
use App\Enums\Rol;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Support\Media;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Cotizacion\CotizacionTestCase;

class ComprasTest extends CotizacionTestCase
{
    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proveedor = Proveedor::create(['nombre' => 'Refaccionaria del Valle', 'telefono' => '7225551234', 'dias_entrega' => 2]);
    }

    /** Orden aprobada con mano de obra + refacción "Balatas delanteras" (sin disponibilidad = por pedir). */
    private function ordenConPiezaPorPedir(): OrdenServicio
    {
        $orden = $this->orden();
        $c = $this->cotizacionConPartidas($orden);
        $c->partidas()->where('tipo', 'refaccion')->update(['numero_parte' => 'BAL-4521']);
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'cliente', [], 'Laura');

        return $orden->fresh();
    }

    public function test_al_autorizar_nace_la_requisicion_y_el_auto_espera(): void
    {
        $orden = $this->ordenConPiezaPorPedir();

        $r = $orden->requisiciones()->first();
        $this->assertSame('Balatas delanteras', $r->descripcion);
        $this->assertSame('BAL-4521', $r->numero_parte);
        $this->assertSame(EstadoRequisicion::Pendiente, $r->estado);
        $this->assertSame(Etapa::ESPERA_REFACCIONES, $orden->etapa->clave);
    }

    public function test_flujo_completo_pedido_recepcion_y_regreso_de_espera(): void
    {
        $this->asesor->assignRole(Rol::Asesor->value);
        $jefe = $this->usuario(Rol::JefeTaller);
        $orden = $this->ordenConPiezaPorPedir();
        $r = $orden->requisiciones()->first();

        $oc = app(CrearOrdenCompra::class)->desdeRequisiciones(collect([$r]), $this->proveedor, $this->asesor);
        $this->assertMatchesRegularExpression('/^OC-\d{4}-000001$/', $oc->folio);
        $this->assertSame(EstadoRequisicion::Ordenada, $r->fresh()->estado);
        $this->assertSame(today()->addDays(2)->toDateString(), $oc->fecha_estimada->toDateString());
        $this->assertSame(1000.0, $oc->subtotal, 'Toma el costo capturado en la cotización');

        app(CambiarEstadoCompra::class)->enviar($oc);

        // Llega con factura: el costo real sale del XML.
        Media::disco()->put('facturas-proveedor/f.xml', file_get_contents(base_path('tests/fixtures/cfdi-proveedor.xml')));
        $linea = $oc->partidas()->first();
        app(RecibirMercancia::class)($oc->fresh(), [$linea->id => ['cantidad' => 1, 'costo_real' => 950]], $this->asesor, 'facturas-proveedor/f.xml');

        $this->assertSame(EstadoOrdenCompra::Recibida, $oc->fresh()->estado);
        $this->assertSame(EstadoRequisicion::Recibida, $r->fresh()->estado);
        $this->assertSame(950.0, $r->partida->fresh()->costo_real, 'El costo real llega a la partida para la utilidad');
        $this->assertSame('A1B2C3D4-0000-4000-8000-123456789ABC', $oc->recepciones()->first()->factura_uuid);

        // Vino de recepción: sale de espera hacia diagnóstico y se avisa.
        $this->assertSame('diagnostico', $orden->fresh()->etapa->clave);
        $this->assertSame(1, $jefe->notifications()->count());
        $this->assertStringContainsString('Refacciones completas', $jefe->notifications()->first()->data['title']);
    }

    public function test_la_misma_factura_no_se_registra_dos_veces(): void
    {
        $p = Producto::create(['sku' => 'X', 'nombre' => 'X', 'categoria' => 'otro', 'unidad' => 'pza', 'stock_minimo' => 2]);
        $oc = app(CrearOrdenCompra::class)->reposicion(collect([$p]), $this->proveedor);
        app(CambiarEstadoCompra::class)->enviar($oc);
        Media::disco()->put('f.xml', file_get_contents(base_path('tests/fixtures/cfdi-proveedor.xml')));
        $linea = $oc->partidas()->first();

        app(RecibirMercancia::class)($oc->fresh(), [$linea->id => ['cantidad' => 1]], null, 'f.xml');

        $this->expectException(ValidationException::class);
        app(RecibirMercancia::class)($oc->fresh(), [$linea->id => ['cantidad' => 1]], null, 'f.xml');
    }

    public function test_recepcion_parcial_y_reposicion_de_stock(): void
    {
        $p = Producto::create(['sku' => 'AC', 'nombre' => 'Aceite', 'categoria' => 'aceites', 'unidad' => 'l', 'stock_minimo' => 5, 'stock_maximo' => 20]);
        $oc = app(CrearOrdenCompra::class)->reposicion(collect([$p]), $this->proveedor);
        $linea = $oc->partidas()->first();
        $this->assertSame(20.0, $linea->cantidad, 'Sugiere reponer hasta el máximo');

        app(CambiarEstadoCompra::class)->enviar($oc);
        app(RecibirMercancia::class)($oc->fresh(), [$linea->id => ['cantidad' => 12, 'costo_real' => 210]]);
        $this->assertSame(EstadoOrdenCompra::RecibidaParcial, $oc->fresh()->estado);
        $this->assertSame(12.0, $p->fresh()->existencia);
        $this->assertSame(210.0, $p->fresh()->costo_promedio);

        try {
            app(RecibirMercancia::class)($oc->fresh(), [$linea->id => ['cantidad' => 9]]);
            $this->fail('No se puede recibir de más');
        } catch (ValidationException) {
        }

        app(RecibirMercancia::class)($oc->fresh(), [$linea->id => ['cantidad' => 8, 'costo_real' => 230]]);
        $this->assertSame(EstadoOrdenCompra::Recibida, $oc->fresh()->estado);
        $this->assertSame(218.0, $p->fresh()->costo_promedio); // (12×210 + 8×230) / 20
    }

    public function test_pieza_que_surte_la_aseguradora(): void
    {
        $orden = $this->ordenConPiezaPorPedir();
        $r = $orden->requisiciones()->first();
        $r->update(['surte' => 'aseguradora']);

        try {
            app(CrearOrdenCompra::class)->desdeRequisiciones(collect([$r]), $this->proveedor);
            $this->fail('Las piezas de aseguradora no se compran');
        } catch (ValidationException) {
        }

        app(RecibirMercancia::class)->deAseguradora($r->fresh());
        $this->assertSame(EstadoRequisicion::Recibida, $r->fresh()->estado);
        $this->assertSame('diagnostico', $orden->fresh()->etapa->clave);
    }

    public function test_cancelar_la_compra_regresa_las_piezas_a_por_pedir(): void
    {
        $r = $this->ordenConPiezaPorPedir()->requisiciones()->first();
        $oc = app(CrearOrdenCompra::class)->desdeRequisiciones(collect([$r]), $this->proveedor);

        app(CambiarEstadoCompra::class)->cancelar($oc);

        $this->assertSame(EstadoOrdenCompra::Cancelada, $oc->fresh()->estado);
        $this->assertSame(EstadoRequisicion::Pendiente, $r->fresh()->estado);
    }

    public function test_aviso_diario_de_compras_atrasadas(): void
    {
        $almacenista = $this->usuario(Rol::Almacen);
        $r = $this->ordenConPiezaPorPedir()->requisiciones()->first();
        $oc = app(CrearOrdenCompra::class)->desdeRequisiciones(collect([$r]), $this->proveedor);
        app(CambiarEstadoCompra::class)->enviar($oc);
        $oc->update(['fecha_estimada' => today()->subDays(2)]);

        $this->assertTrue($oc->fresh()->atrasada());
        $this->artisan('tarzo:revisar-compras')->assertSuccessful();
        $this->artisan('tarzo:revisar-compras')->assertSuccessful();

        $this->assertSame(1, $almacenista->notifications()->count(), 'Un aviso por día');
    }

    public function test_el_suplemento_conserva_la_requisicion(): void
    {
        $orden = $this->ordenConPiezaPorPedir();
        $c2 = app(CrearSuplemento::class)($orden->cotizacionActual);

        $this->assertSame(1, Requisicion::count());
        $this->assertSame($c2->partidas()->where('tipo', 'refaccion')->value('id'), Requisicion::first()->partida_id);
    }
}
