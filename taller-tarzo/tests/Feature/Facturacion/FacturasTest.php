<?php

namespace Tests\Feature\Facturacion;

use App\Actions\Facturacion\CancelarPago;
use App\Actions\Facturacion\CrearNotaCredito;
use App\Actions\Facturacion\CrearSustituta;
use App\Actions\Facturacion\PrepararFacturas;
use App\Actions\Facturacion\RegistrarPago;
use App\Enums\EstadoFactura;
use App\Enums\EstadoPartida;
use App\Models\Factura;
use App\Services\Cfdi\Facturador;
use App\Services\Cotizacion\Contexto;
use App\Services\Cotizacion\Cotizador;
use App\Support\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FacturasTest extends FacturacionTestCase
{
    public function test_factura_particular_con_partidas_autorizadas_y_timbrado_simulado(): void
    {
        $orden = $this->ordenParticular();
        $facturas = app(PrepararFacturas::class)($orden, $this->contador);

        $this->assertCount(1, $facturas);
        $f = $facturas->first();
        $this->assertSame(EstadoFactura::Borrador, $f->estado);
        $this->assertSame('GODE561231GR8', $f->receptor_rfc);
        $this->assertSame('PUE', $f->metodo_pago);
        $this->assertEqualsWithDelta(2300, $f->subtotal, 0.001);
        $this->assertEqualsWithDelta(368, $f->iva, 0.001);
        $this->assertEqualsWithDelta(2668, $f->total, 0.001);
        $this->assertSame(['78181500', '25174000'], $f->conceptos->pluck('clave_prod_serv')->all());

        app(Facturador::class)->timbrar($f);
        $f->refresh();
        $this->assertSame(EstadoFactura::Timbrada, $f->estado);
        $this->assertMatchesRegularExpression('/^[0-9A-F-]{36}$/', $f->uuid);
        $this->assertEqualsWithDelta(2668, $f->saldo, 0.001);
        $xml = Media::disco()->get($f->xml_path);
        $this->assertStringContainsString('Version="4.0"', $xml);
        $this->assertStringContainsString('UUID="'.$f->uuid.'"', $xml);
        $this->assertStringContainsString('Total="2668.00"', $xml);
        $this->assertStringStartsWith('%PDF', Media::disco()->get($f->pdf_path));

        $this->expectException(ValidationException::class);
        app(PrepararFacturas::class)($orden, $this->contador);
    }

    public function test_partidas_en_cero_no_se_facturan(): void
    {
        $orden = $this->ordenParticular();
        $orden->cotizacionActual->partidas()->where('tipo', 'mano_obra')->update(['importe' => 0]);

        $f = app(PrepararFacturas::class)($orden, $this->contador)->first();

        $this->assertCount(1, $f->conceptos);
        $this->assertTrue($f->conceptos->every(fn ($c) => $c->valor_unitario > 0));
    }

    public function test_sin_datos_fiscales_no_factura(): void
    {
        $this->expectExceptionMessage('datos fiscales');
        app(PrepararFacturas::class)($this->ordenAprobada(), $this->contador);
    }

    public function test_orden_de_seguro_se_divide_entre_aseguradora_y_deducible_del_cliente(): void
    {
        $orden = $this->ordenSeguro(500);
        $c = $orden->cotizacionActual;
        $t = app(Cotizador::class)->totales($c->partidas()->where('estado', EstadoPartida::Aprobada->value)->get()->map->toArray(), Contexto::paraCotizacion($c));

        $facturas = app(PrepararFacturas::class)($orden, $this->contador);
        $aseg = $facturas->firstWhere('receptor_tipo', 'aseguradora');
        $cli = $facturas->firstWhere('receptor_tipo', 'cliente');

        $this->assertSame('PPD', $aseg->metodo_pago);
        $this->assertSame('99', $aseg->forma_pago->value);
        $this->assertNotNull($aseg->vence);
        $this->assertEqualsWithDelta($t['monto_aseguradora'], $aseg->total, 0.02, 'Total aseguradora = cubierto − deducible');
        $this->assertGreaterThan(0, $aseg->descuento);
        $this->assertEqualsWithDelta(500, $cli->total, 0.02, 'Al cliente solo el deducible');
        $this->assertStringContainsString('Deducible del siniestro S-2027-1', $cli->conceptos->first()->descripcion);
        $this->assertEqualsWithDelta($t['monto_aseguradora'] + $t['monto_cliente'], $aseg->total + $cli->total, 0.03);
    }

    public function test_cobro_de_contado_y_complemento_de_pago_para_ppd(): void
    {
        $f = $this->facturaTimbrada();
        [$pago, $error] = app(RegistrarPago::class)(['cliente_id' => $f->cliente_id, 'forma_pago' => '04', 'monto' => 2668], [$f->id => 2668], $this->contador);
        $this->assertNull($error);
        $this->assertSame(0.0, $f->fresh()->saldo);
        $this->assertNull($pago->complemento, 'PUE no lleva complemento');

        // Aseguradora a crédito: dos parcialidades con REP
        $orden = $this->ordenSeguro();
        $aseg = app(PrepararFacturas::class)($orden, $this->contador)->firstWhere('receptor_tipo', 'aseguradora');
        app(Facturador::class)->timbrar($aseg);
        $aseg->refresh();
        $mitad = round($aseg->total / 2, 2);

        [$p1, $err1] = app(RegistrarPago::class)(['aseguradora_id' => $aseg->aseguradora_id, 'forma_pago' => '03', 'monto' => $mitad], [$aseg->id => $mitad], $this->contador);
        $this->assertNull($err1);
        $rep = $p1->complemento->refresh();
        $this->assertSame(Factura::PAGO, $rep->tipo);
        $this->assertTrue($rep->timbrada());
        $xml = Media::disco()->get($rep->xml_path);
        $this->assertStringContainsString('pago20:Pagos', $xml);
        $this->assertStringContainsString('NumParcialidad="1"', $xml);
        $this->assertStringContainsString('ImpSaldoInsoluto="'.number_format($aseg->total - $mitad, 2, '.', '').'"', $xml);

        [$p2] = app(RegistrarPago::class)(['aseguradora_id' => $aseg->aseguradora_id, 'forma_pago' => '03', 'monto' => $aseg->total - $mitad], [$aseg->id => $aseg->total - $mitad], $this->contador);
        $this->assertStringContainsString('NumParcialidad="2"', Media::disco()->get($p2->complemento->xml_path));
        $this->assertSame(0.0, $aseg->fresh()->saldo);
    }

    public function test_anticipo_y_cancelacion_de_pago(): void
    {
        $f = $this->facturaTimbrada();
        [$anticipo] = app(RegistrarPago::class)(['cliente_id' => $f->cliente_id, 'forma_pago' => '01', 'monto' => 1000]);
        $this->assertTrue($anticipo->es_anticipo);
        $this->assertSame(1000.0, $anticipo->por_aplicar);

        app(RegistrarPago::class)->aplicarExistente($anticipo, [$f->id => 1000]);
        $this->assertEqualsWithDelta(1668, $f->fresh()->saldo, 0.001);
        $this->assertSame(0.0, $anticipo->fresh()->por_aplicar);

        try {
            app(RegistrarPago::class)(['cliente_id' => $f->cliente_id, 'forma_pago' => '01', 'monto' => 100], [$f->id => 5000]);
            $this->fail('No puede aplicar más de lo pagado');
        } catch (ValidationException) {
        }

        app(CancelarPago::class)($anticipo, 'Capturado dos veces');
        $this->assertEqualsWithDelta(2668, $f->fresh()->saldo, 0.001);
        $this->assertNotNull($anticipo->fresh()->cancelado_at);
    }

    public function test_nota_de_credito_reduce_el_saldo(): void
    {
        $f = $this->facturaTimbrada();
        $nota = app(CrearNotaCredito::class)($f, 580, 'Bonificación por retraso', $this->contador);

        $this->assertSame(Factura::EGRESO, $nota->tipo);
        $this->assertSame('01', $nota->relacion_tipo);
        $this->assertEqualsWithDelta(500, $nota->subtotal, 0.001);
        $this->assertSame('84111506', $nota->conceptos->first()->clave_prod_serv);

        app(Facturador::class)->timbrar($nota);
        $this->assertEqualsWithDelta(2088, $f->fresh()->saldo, 0.001);
        $this->assertStringContainsString('TipoRelacion="01"', Media::disco()->get($nota->fresh()->xml_path));

        $this->expectException(ValidationException::class);
        app(CrearNotaCredito::class)($f->fresh(), 5000, 'Excede', $this->contador);
    }

    public function test_cancelacion_con_motivo_y_sustitucion(): void
    {
        $f = $this->facturaTimbrada();

        try {
            app(Facturador::class)->cancelar($f, '01');
            $this->fail('Motivo 01 requiere la sustituta');
        } catch (ValidationException) {
        }

        $nueva = app(CrearSustituta::class)($f, $this->contador);
        $this->assertSame('04', $nueva->relacion_tipo);
        $this->assertEqualsWithDelta($f->total, $nueva->total, 0.001);
        app(Facturador::class)->timbrar($nueva);

        app(Facturador::class)->cancelar($f, '01', $nueva->fresh());
        $f->refresh();
        $this->assertSame(EstadoFactura::Cancelada, $f->estado);
        $this->assertSame(0.0, $f->saldo);

        // Con pagos aplicados no se cancela
        $g = $nueva->fresh();
        app(RegistrarPago::class)(['cliente_id' => $g->cliente_id, 'forma_pago' => '01', 'monto' => 100], [$g->id => 100]);
        $this->expectExceptionMessage('pagos aplicados');
        app(Facturador::class)->cancelar($g, '02');
    }

    public function test_driver_facturama_arma_el_cfdi_y_guarda_lo_que_regresa(): void
    {
        config(['tarzo.cfdi.driver' => 'facturama', 'tarzo.cfdi.usuario' => 'u', 'tarzo.cfdi.password' => 'p']);
        Http::fake([
            'apisandbox.facturama.mx/api-lite/3/cfdis' => Http::response(['Id' => 'fac-123', 'Complement' => ['TaxStamp' => ['Uuid' => 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE']]]),
            'apisandbox.facturama.mx/cfdi/xml/*' => Http::response(['Content' => base64_encode('<cfdi:Comprobante Version="4.0"/>')]),
            'apisandbox.facturama.mx/cfdi/pdf/*' => Http::response(['Content' => base64_encode('%PDF-1.4 facturama')]),
            'apisandbox.facturama.mx/api-lite/cfdis/*' => Http::response(['Status' => 'canceled']),
        ]);

        $f = $this->facturaTimbrada();

        $this->assertSame('AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', $f->uuid);
        $this->assertSame('fac-123', $f->pac_id);
        $this->assertSame('%PDF-1.4 facturama', Media::disco()->get($f->pdf_path));
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/api-lite/3/cfdis')
            && $r['CfdiType'] === 'I' && $r['PaymentMethod'] === 'PUE' && $r['Receiver']['Rfc'] === 'GODE561231GR8'
            && $r['Receiver']['FiscalRegime'] === '612' && count($r['Items']) === 2 && $r['Items'][0]['Taxes'][0]['Rate'] === 0.16);

        app(Facturador::class)->cancelar($f, '02');
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && str_contains($r->url(), 'fac-123?motive=02'));
        $this->assertSame(EstadoFactura::Cancelada, $f->fresh()->estado);
    }

    public function test_rechazo_del_pac_deja_la_factura_con_error(): void
    {
        config(['tarzo.cfdi.driver' => 'facturama', 'tarzo.cfdi.usuario' => 'u', 'tarzo.cfdi.password' => 'p']);
        Http::fake(['*' => Http::response(['Message' => 'La solicitud no es válida', 'ModelState' => ['Receiver.Rfc' => ['El RFC no está en la lista de contribuyentes']]], 400)]);
        $f = app(PrepararFacturas::class)($this->ordenParticular(), $this->contador)->first();

        try {
            app(Facturador::class)->timbrar($f);
            $this->fail('Debe fallar');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('lista de contribuyentes', collect($e->errors())->flatten()->first());
        }

        $this->assertSame(EstadoFactura::Error, $f->fresh()->estado);
        $this->assertTrue($f->fresh()->editable(), 'Se corrige y se vuelve a timbrar');
    }
}
