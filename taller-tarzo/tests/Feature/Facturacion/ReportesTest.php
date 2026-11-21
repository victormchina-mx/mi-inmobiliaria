<?php

namespace Tests\Feature\Facturacion;

use App\Actions\Facturacion\PrepararFacturas;
use App\Enums\Rol;
use App\Filament\Pages\CuentasPorCobrar;
use App\Filament\Pages\Indicadores as PaginaIndicadores;
use App\Filament\Resources\Facturas\Pages\ViewFactura;
use App\Filament\Resources\Pagos\Pages\ManagePagos;
use App\Models\Etapa;
use App\Models\GastoOrden;
use App\Models\HistorialEtapa;
use App\Models\Pago;
use App\Models\Parametro;
use App\Models\RegistroTiempo;
use App\Models\Tecnico;
use App\Services\Finanzas\Indicadores;
use App\Services\Finanzas\Rentabilidad;
use App\Support\ExportarIndicadores;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

class ReportesTest extends FacturacionTestCase
{
    public function test_utilidad_de_la_orden_con_costos_reales(): void
    {
        $orden = $this->ordenParticular();
        $tecnico = $this->tecnicoConUsuario();
        $tecnico->update(['costo_hora' => 120]);
        $tarea = $orden->tareas()->first();
        RegistroTiempo::create(['tarea_id' => $tarea->id, 'tecnico_id' => $tecnico->id, 'inicio' => now()->subMinutes(90), 'fin' => now(), 'minutos' => 90]);
        GastoOrden::create(['orden_servicio_id' => $orden->id, 'concepto' => 'Grúa', 'monto' => 200, 'fecha' => today()]);
        $orden->requisiciones()->update(['costo_real' => 950]);

        $u = app(Rentabilidad::class)->calcular($orden);
        $this->assertTrue($u['ingreso_estimado'], 'Sin factura se estima con la cotización');
        $this->assertEqualsWithDelta(2300, $u['ingreso'], 0.01);
        $this->assertEqualsWithDelta(180, $u['costo_mano_obra'], 0.01, '1.5 h × $120');
        $this->assertEqualsWithDelta(200, $u['costo_subcontratos'], 0.01);
        $refacciones = $orden->requisiciones()->where('surte', 'taller')->exists() ? 950 : 0;
        $this->assertEqualsWithDelta($refacciones, $u['costo_refacciones'], 0.01);
        $this->assertEqualsWithDelta(2300 - 180 - 200 - $refacciones, $u['utilidad'], 0.01);

        // Al timbrar, el ingreso pasa a ser el facturado y el cierre se actualiza.
        app(Rentabilidad::class)->cerrar($orden);
        $f = $this->facturaTimbrada($orden);
        $cierre = $orden->cierre()->first();
        $this->assertFalse($cierre->ingreso_estimado);
        $this->assertEqualsWithDelta($f->subtotal, $cierre->ingreso, 0.01);
    }

    public function test_avisa_cuando_el_margen_queda_bajo_el_minimo(): void
    {
        Parametro::where('clave', 'margen_minimo')->update(['valor' => 99]);
        Cache::forget('parametros');
        $orden = $this->ordenParticular();
        GastoOrden::create(['orden_servicio_id' => $orden->id, 'concepto' => 'Rectificado', 'monto' => 100, 'fecha' => today()]);

        app(Rentabilidad::class)->cerrar($orden);
        app(Rentabilidad::class)->cerrar($orden); // no repite el aviso

        $this->assertSame(1, $this->contador->notifications()->count());
    }

    public function test_costo_de_hora_hombre(): void
    {
        $this->assertSame(147.66, Tecnico::calcularCostoHora(21000, 1.35, 192));
        $this->assertNull(Tecnico::calcularCostoHora(null, 1.35, 192));
    }

    public function test_indicadores_del_mes_y_cuello_de_botella(): void
    {
        $orden = $this->ordenParticular();
        $diag = Etapa::where('clave', 'diagnostico')->first();
        $mec = Etapa::where('clave', 'mecanica')->first();
        HistorialEtapa::where('orden_servicio_id', $orden->id)->update(['created_at' => now()->subDays(6)]);
        HistorialEtapa::forceCreate(['orden_servicio_id' => $orden->id, 'etapa_id' => $diag->id, 'created_at' => now()->subDays(5)]);
        HistorialEtapa::forceCreate(['orden_servicio_id' => $orden->id, 'etapa_id' => $mec->id, 'created_at' => now()->subDays(1)]);
        HistorialEtapa::forceCreate(['orden_servicio_id' => $orden->id, 'etapa_id' => Etapa::where('clave', Etapa::ENTREGADO)->value('id'), 'created_at' => now()]);
        $orden->forceFill(['fecha_ingreso' => now()->subDays(6), 'fecha_promesa' => now()->addDay(), 'fecha_entrega' => now(), 'cerrada_at' => now()])->save();
        app(Rentabilidad::class)->cerrar($orden);
        $this->facturaTimbrada($orden);

        $d = app(Indicadores::class)->calcular(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(1, $d['kpis']['entregados']['valor']);
        $this->assertEqualsWithDelta(6, $d['kpis']['ciclo']['valor'], 0.1);
        $this->assertSame(100.0, $d['kpis']['promesa']['valor']);
        $this->assertEqualsWithDelta(2300, $d['kpis']['ingreso']['valor'], 0.01);
        $this->assertSame(100.0, $d['kpis']['aprobacion']['valor']);
        $this->assertSame('Diagnóstico', $d['cuello']['etapa'], '4 días en diagnóstico');
        $this->assertNotEmpty($d['kpis']['ciclo']['definicion']);

        $this->assertStringStartsWith('PK', ExportarIndicadores::xlsx(now()->startOfMonth(), now()->endOfMonth()));
        $this->assertCount(6, app(Indicadores::class)->tendencia());
    }

    public function test_pantallas_de_facturacion_y_reportes_por_rol(): void
    {
        $f = $this->facturaTimbrada();
        $this->actingAs($this->contador);

        foreach (['facturas', "facturas/{$f->id}", 'caja', 'cuentas-por-cobrar', 'indicadores', 'utilidad-por-orden'] as $ruta) {
            $this->get("/panel/{$ruta}")->assertOk();
        }
        $this->get(route('facturas.archivo', [$f, 'xml']))->assertOk()->assertDownload();
        $this->get(route('facturas.archivo', [$f, 'pdf']))->assertOk();

        Livewire::test(CuentasPorCobrar::class)->assertSee($f->folio_completo)->assertSee('Particulares');
        Livewire::test(PaginaIndicadores::class)->assertSee('Ciclo de reparación')->assertSee('Días promedio por etapa');

        Livewire::test(ManagePagos::class)->callAction('cobrar', [
            'pagador' => 'cliente', 'cliente_id' => $f->cliente_id, 'fecha' => now()->toDateTimeString(), 'forma_pago' => '01',
            'monto' => 3000, 'aplicar' => [['factura_id' => $f->id, 'monto' => 2668]],
        ])->assertHasNoActionErrors();
        $pago = Pago::sole();
        $this->assertEqualsWithDelta(332, $pago->por_aplicar, 0.001);
        $this->get(route('caja.recibo', $pago))->assertOk();

        $this->actingAs($this->usuario(Rol::Tecnico));
        $this->get('/panel/facturas')->assertForbidden();
        $this->get('/panel/indicadores')->assertForbidden();
        $this->get(route('facturas.archivo', [$f, 'xml']))->assertForbidden();
    }

    public function test_timbrar_desde_la_pantalla(): void
    {
        $f = app(PrepararFacturas::class)($this->ordenParticular(), $this->contador)->first();
        $this->actingAs($this->contador);

        Livewire::test(ViewFactura::class, ['record' => $f->id])->callAction('timbrar');

        $this->assertTrue($f->fresh()->timbrada());
    }
}
