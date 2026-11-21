<?php

namespace Tests\Feature\Almacen;

use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Models\Producto;
use App\Services\Inventario\Inventario;
use App\Services\Inventario\LectorCfdi;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Cotizacion\CotizacionTestCase;

class InventarioTest extends CotizacionTestCase
{
    private Inventario $inv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inv = app(Inventario::class);
    }

    private function producto(array $datos = []): Producto
    {
        return Producto::create([
            'sku' => 'AC-5W30', 'codigo_barras' => '7501234567890', 'nombre' => 'Aceite sintético 5W-30',
            'categoria' => 'aceites', 'unidad' => 'l', 'stock_minimo' => 0, 'stock_maximo' => 0, ...$datos,
        ]);
    }

    public function test_costo_promedio_ponderado(): void
    {
        $p = $this->producto();

        $this->inv->entrada($p, 10, 200);  // 10 × 200
        $this->inv->entrada($p, 10, 230);  // promedio (2,000 + 2,300) / 20 = 215
        $this->assertSame(20.0, $p->existencia);
        $this->assertSame(215.0, $p->costo_promedio);

        $salida = $this->inv->salida($p, 4, $this->orden());
        $this->assertSame(TipoMovimiento::Salida, $salida->tipo);
        $this->assertSame(-4.0, $salida->cantidad);
        $this->assertSame(860.0, $salida->costo_total, 'La salida se valora al promedio vigente');
        $this->assertSame(16.0, $p->existencia);
        $this->assertSame(215.0, $p->costo_promedio, 'Las salidas no cambian el promedio');
    }

    public function test_toda_salida_va_a_una_orden_o_a_gasto_general(): void
    {
        $p = $this->producto();
        $this->inv->entrada($p, 5, 100);

        try {
            $this->inv->salida($p, 1, null);
            $this->fail('Debió exigir orden o motivo');
        } catch (ValidationException) {
        }

        $m = $this->inv->salida($p, 1, null, null, 'Limpieza del taller');
        $this->assertTrue($m->gasto_general);
        $this->assertSame('Limpieza del taller', $m->referencia);
    }

    public function test_no_se_saca_mas_de_lo_que_hay(): void
    {
        $p = $this->producto();
        $this->inv->entrada($p, 2, 100);

        $this->expectException(ValidationException::class);
        $this->inv->salida($p, 3, $this->orden());
    }

    public function test_devolucion_solo_hasta_lo_que_salio_a_la_orden(): void
    {
        $p = $this->producto();
        $orden = $this->orden();
        $this->inv->entrada($p, 10, 100);
        $this->inv->salida($p, 4, $orden);

        $this->inv->devolucion($p, 1, $orden);
        $this->assertSame(7.0, $p->existencia);

        $this->expectException(ValidationException::class);
        $this->inv->devolucion($p, 4, $orden);
    }

    public function test_ajuste_por_conteo(): void
    {
        $p = $this->producto();
        $this->inv->entrada($p, 10, 100);

        $this->assertNull($this->inv->ajustarA($p, 10), 'Si coincide no hay movimiento');
        $m = $this->inv->ajustarA($p, 8.5);
        $this->assertSame(-1.5, $m->cantidad);
        $this->assertSame(8.5, $p->existencia);
    }

    public function test_alerta_de_minimo_una_sola_vez_y_se_reinicia_al_reponer(): void
    {
        $almacenista = $this->usuario(Rol::Almacen);
        $p = $this->producto(['stock_minimo' => 5, 'stock_maximo' => 20]);
        $orden = $this->orden();
        $this->inv->entrada($p, 8, 100);

        $this->inv->salida($p, 4, $orden);  // 4 < 5: cruza el mínimo
        $this->inv->salida($p, 1, $orden);  // sigue abajo: no repite
        $this->assertSame(1, $almacenista->notifications()->count());
        $this->assertStringContainsString('Stock bajo', $almacenista->notifications()->first()->data['title']);
        $this->assertSame(17.0, $p->fresh()->sugerenciaCompra());

        $this->inv->entrada($p, 10, 100);   // repone
        $this->assertNull($p->fresh()->alerta_minimo_at);
        $this->inv->salida($p, 10, $orden); // vuelve a cruzar
        $this->assertSame(2, $almacenista->notifications()->count());
    }

    public function test_lee_el_cfdi_del_proveedor(): void
    {
        $f = app(LectorCfdi::class)->leer(file_get_contents(base_path('tests/fixtures/cfdi-proveedor.xml')));

        $this->assertSame('A1B2C3D4-0000-4000-8000-123456789ABC', $f['uuid']);
        $this->assertSame('REFACCIONARIA DEL VALLE', $f['nombre_emisor']);
        $this->assertSame(3654.0, $f['total']);
        $this->assertCount(2, $f['conceptos']);
        $this->assertSame(950.0, LectorCfdi::emparejar($f['conceptos'], 'bal-4521', 'otra cosa')['valor_unitario']);
        $this->assertSame(220.0, LectorCfdi::emparejar($f['conceptos'], null, 'Aceite sintético 5W-30')['valor_unitario']);
        $this->assertNull(LectorCfdi::emparejar($f['conceptos'], null, 'Filtro de aire'));
    }

    public function test_rechaza_xml_que_no_es_cfdi_y_no_expande_entidades_externas(): void
    {
        $xxe = '<?xml version="1.0"?><!DOCTYPE c [<!ENTITY x SYSTEM "file:///etc/passwd">]>'
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Total="1"><cfdi:Conceptos><cfdi:Concepto Descripcion="&x;" Cantidad="1" ValorUnitario="1" Importe="1"/></cfdi:Conceptos></cfdi:Comprobante>';

        // O se rechaza, o se lee sin expandir la entidad: nunca debe aparecer el contenido del archivo.
        try {
            $f = app(LectorCfdi::class)->leer($xxe);
            $this->assertStringNotContainsString('root:', json_encode($f));
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(ValidationException::class);
        app(LectorCfdi::class)->leer('<html><body>no</body></html>');
    }
}
