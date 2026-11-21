<?php

namespace Tests\Feature\Almacen;

use App\Actions\Almacen\CambiarEstadoCompra;
use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Enums\EstadoOrdenCompra;
use App\Enums\Rol;
use App\Filament\Pages\ConteoInventario;
use App\Filament\Resources\OrdenesCompra\Pages\EditOrdenCompra;
use App\Filament\Resources\OrdenServicios\Pages\ViewOrdenServicio;
use App\Filament\Resources\OrdenServicios\RelationManagers\ConsumosRelationManager;
use App\Filament\Resources\Productos\Pages\CreateProducto;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\Widgets\ResumenInventario;
use App\Filament\Resources\Requisiciones\Pages\ListRequisiciones;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\Inventario\Inventario;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\Feature\Cotizacion\CotizacionTestCase;

class PantallasAlmacenTest extends CotizacionTestCase
{
    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->usuario(Rol::Almacen));
        $this->proveedor = Proveedor::create(['nombre' => 'Refaccionaria del Valle', 'dias_entrega' => 1]);
    }

    public function test_alta_de_producto_y_entrada_desde_la_tabla(): void
    {
        Livewire::test(CreateProducto::class)
            ->fillForm(['nombre' => 'Lija 400', 'sku' => 'LIJ-400', 'categoria' => 'abrasivos', 'unidad' => 'hoja', 'stock_minimo' => 50, 'stock_maximo' => 200])
            ->call('create')->assertHasNoFormErrors();

        $p = Producto::firstWhere('sku', 'LIJ-400');

        Livewire::test(ListProductos::class)
            ->callAction(TestAction::make('entrada')->table($p), data: ['cantidad' => 100, 'costo' => 8.5])
            ->assertHasNoActionErrors();

        Livewire::test(ResumenInventario::class)
            ->assertSee('Valor del inventario')->assertSee('$850.00')->assertSee('Bajo el mínimo');

        $this->assertSame(100.0, $p->fresh()->existencia);
        $this->assertSame(8.5, $p->fresh()->costo_promedio);
    }

    public function test_cargar_material_a_una_orden(): void
    {
        $p = Producto::create(['sku' => 'AC', 'nombre' => 'Aceite', 'categoria' => 'aceites', 'unidad' => 'l']);
        app(Inventario::class)->entrada($p, 10, 200);
        $orden = $this->orden();

        Livewire::test(ConsumosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => ViewOrdenServicio::class])
            ->callAction(TestAction::make('salida')->table(), data: ['producto_id' => $p->id, 'cantidad' => 4])
            ->assertHasNoActionErrors();

        $this->assertSame(6.0, $p->fresh()->existencia);
        $this->assertSame(800.0, (float) $orden->consumos()->first()->costo_total);
    }

    public function test_piezas_por_pedir_a_orden_de_compra_y_recepcion(): void
    {
        $orden = $this->orden();
        $c = $this->cotizacionConPartidas($orden);
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'cliente', [], 'Laura');
        $r = $orden->requisiciones()->first();

        Livewire::test(ListRequisiciones::class)
            ->assertSee('Balatas delanteras')
            ->selectTableRecords([$r->id])
            ->callAction(TestAction::make('ordenCompra')->table()->bulk(), data: ['proveedor_id' => $this->proveedor->id])
            ->assertHasNoActionErrors();

        $oc = OrdenCompra::first();
        app(CambiarEstadoCompra::class)->enviar($oc);
        $linea = $oc->partidas()->first();

        Livewire::test(EditOrdenCompra::class, ['record' => $oc->getRouteKey()])
            ->callAction('recibir', data: ['lineas' => [['id' => $linea->id, 'descripcion' => $linea->descripcion, 'cantidad' => 1, 'costo_real' => 980]]])
            ->assertHasNoActionErrors();

        $this->assertSame(EstadoOrdenCompra::Recibida, $oc->fresh()->estado);
        $this->assertSame(980.0, $r->fresh()->costo_real);
    }

    public function test_conteo_fisico_con_lector(): void
    {
        $p = Producto::create(['sku' => 'FIL-01', 'codigo_barras' => '7500000000011', 'nombre' => 'Filtro de aceite', 'categoria' => 'filtros', 'unidad' => 'pza']);
        app(Inventario::class)->entrada($p, 5, 90);

        Livewire::test(ConteoInventario::class)
            ->set('codigo', '7500000000011')->call('agregar')
            ->set('codigo', '7500000000011')->call('agregar')
            ->set('codigo', 'NO-EXISTE')->call('agregar')->assertNotified()
            ->assertSet("conteo.{$p->id}.contado", 2.0)
            ->set("conteo.{$p->id}.contado", 4)
            ->call('aplicar');

        $this->assertSame(4.0, $p->fresh()->existencia);
    }

    public function test_permisos_de_almacen(): void
    {
        $this->actingAs($this->asesor);
        $this->get('/panel/productos')->assertOk();
        $this->get('/panel/productos/create')->assertForbidden();
        $this->get('/panel/conteo-inventario')->assertForbidden();

        $this->actingAs($this->usuario(Rol::Tecnico));
        $this->get('/panel/productos')->assertForbidden();
        $this->get('/panel/ordenes-compra')->assertForbidden();
    }
}
