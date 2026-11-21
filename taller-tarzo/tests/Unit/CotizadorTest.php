<?php

namespace Tests\Unit;

use App\Services\Cotizacion\Contexto;
use App\Services\Cotizacion\Cotizador;
use PHPUnit\Framework\TestCase;

/**
 * Casos calculados a mano. Áreas: 1 mecánica ($500/h), 2 hojalatería ($450/h), 3 pintura ($480/h).
 * Pieza 10: facia delantera 1.1 m². Pintura sólida $400/m², metálica $600/m².
 */
class CotizadorTest extends TestCase
{
    private Cotizador $cotizador;

    protected function setUp(): void
    {
        $this->cotizador = new Cotizador;
    }

    private function contexto(array $cambios = []): Contexto
    {
        return new Contexto(...[
            'tarifasArea' => [1 => 500, 2 => 450, 3 => 480],
            'areaLaminado' => 2,
            'areaPintura' => 3,
            'costoM2' => ['solida' => 400, 'metalica' => 600, 'perlada' => 900],
            'factorDano' => ['leve' => 0.4, 'medio' => 0.7, 'fuerte' => 1.0],
            'superficies' => [10 => 1.1],
            'margenDefecto' => 30,
            'ivaTasa' => 0.16,
            'tarifaCicloCabina' => 800,
            'piezasPorCiclo' => 3,
            ...$cambios,
        ]);
    }

    public function test_mano_de_obra_es_horas_por_tarifa_del_area(): void
    {
        $r = $this->cotizador->calcularPartida(['tipo' => 'mano_obra', 'area_id' => 1, 'horas' => 1.5], $this->contexto());

        $this->assertSame(500.0, $r['tarifa_aplicada']);
        $this->assertSame(750.0, $r['importe']);
    }

    public function test_refaccion_con_margen_por_defecto_y_manual(): void
    {
        $c = $this->contexto();

        // 2 × (1,000 × 1.30) = 2,600
        $this->assertSame(2600.0, $this->cotizador->calcularPartida(['tipo' => 'refaccion', 'cantidad' => 2, 'costo_unitario' => 1000], $c)['importe']);
        // margen propio 50 %: 1 × 1,500
        $this->assertSame(1500.0, $this->cotizador->calcularPartida(['tipo' => 'refaccion', 'costo_unitario' => 1000, 'margen' => 50], $c)['importe']);
        // precio manual manda
        $this->assertSame(1999.0, $this->cotizador->calcularPartida(['tipo' => 'refaccion', 'costo_unitario' => 1000, 'precio_manual' => 1999], $c)['importe']);
    }

    public function test_reparar_y_pintar_una_pieza(): void
    {
        // laminado 3 h × 450 = 1,350; pintura 2 h × 480 = 960;
        // materiales 1.1 m² × 600 × 0.7 (medio) = 462 → 2,772
        $r = $this->cotizador->calcularPartida([
            'tipo' => 'hojalateria_pintura', 'pieza_id' => 10, 'operacion_hyp' => 'reparar',
            'nivel_dano' => 'medio', 'tipo_pintura' => 'metalica', 'horas_laminado' => 3, 'horas_pintura' => 2,
        ], $this->contexto());

        $this->assertSame(462.0, $r['importe_materiales']);
        $this->assertSame(2772.0, $r['importe']);
    }

    public function test_sustituir_pinta_toda_la_pieza_y_suma_la_refaccion(): void
    {
        // pintura 2.5 h × 480 = 1,200; materiales 1.1 × 400 × 1 = 440; pieza 3,000 × 1.30 = 3,900 → 5,540
        $r = $this->cotizador->calcularPartida([
            'tipo' => 'hojalateria_pintura', 'pieza_id' => 10, 'operacion_hyp' => 'sustituir',
            'nivel_dano' => 'leve', 'tipo_pintura' => 'solida', 'horas_pintura' => 2.5, 'costo_unitario' => 3000,
        ], $this->contexto());

        $this->assertSame(440.0, $r['importe_materiales']);
        $this->assertSame(5540.0, $r['importe']);
    }

    public function test_materiales_manuales_y_desmontar_montar(): void
    {
        $c = $this->contexto();

        $manual = $this->cotizador->calcularPartida(['tipo' => 'hojalateria_pintura', 'pieza_id' => 10, 'operacion_hyp' => 'reparar', 'materiales_manual' => 350], $c);
        $this->assertSame(350.0, $manual['importe_materiales']);

        $dm = $this->cotizador->calcularPartida(['tipo' => 'hojalateria_pintura', 'pieza_id' => 10, 'operacion_hyp' => 'desmontar_montar', 'horas_laminado' => 1], $c);
        $this->assertSame(0.0, $dm['importe_materiales']);
        $this->assertSame(450.0, $dm['importe']);
    }

    public function test_totales_particular_con_cabina_e_iva(): void
    {
        $c = $this->contexto();
        $partidas = [
            ['tipo' => 'mano_obra', 'importe' => 1000],
            ['tipo' => 'refaccion', 'importe' => 2000],
            ['tipo' => 'hojalateria_pintura', 'operacion_hyp' => 'reparar', 'importe' => 3000],
            ['tipo' => 'hojalateria_pintura', 'operacion_hyp' => 'pintar', 'importe' => 1000],
            ['tipo' => 'hojalateria_pintura', 'operacion_hyp' => 'desmontar_montar', 'importe' => 500],
            ['tipo' => 'refaccion', 'importe' => 9999, 'estado' => 'rechazada'],
        ];

        $t = $this->cotizador->totales($partidas, $c);

        // 2 piezas pintadas → 1 ciclo × 800
        $this->assertSame(1, $t['ciclos_cabina']);
        $this->assertSame(800.0, $t['importe_cabina']);
        $this->assertSame(8300.0, $t['subtotal']);   // 1000+2000+4500+800 (sin la rechazada)
        $this->assertSame(1328.0, $t['iva']);
        $this->assertSame(9628.0, $t['total']);
        $this->assertSame(9628.0, $t['monto_cliente']);
        $this->assertSame(0.0, $t['monto_aseguradora']);
    }

    public function test_cuatro_piezas_pintadas_son_dos_ciclos(): void
    {
        $partidas = array_fill(0, 4, ['tipo' => 'hojalateria_pintura', 'operacion_hyp' => 'reparar', 'importe' => 100]);

        $this->assertSame(2, $this->cotizador->totales($partidas, $this->contexto())['ciclos_cabina']);
    }

    public function test_seguro_aplica_tabulador_y_reparte_entre_aseguradora_y_cliente(): void
    {
        $c = $this->contexto([
            'esSeguro' => true, 'descuentoRefacciones' => 10, 'descuentoManoObra' => 5,
            'deducible' => 5000, 'demerito' => 300, 'salvamento' => 200, 'tarifaCicloCabina' => 0,
        ]);

        // Refacción a cargo de la aseguradora: 2,000 × 1.30 × 0.90 = 2,340
        $ref = $this->cotizador->calcularPartida(['tipo' => 'refaccion', 'costo_unitario' => 2000, 'paga' => 'aseguradora'], $c);
        $this->assertSame(2340.0, $ref['importe']);
        // La misma refacción a cargo del cliente no lleva descuento: 2,600
        $this->assertSame(2600.0, $this->cotizador->calcularPartida(['tipo' => 'refaccion', 'costo_unitario' => 2000, 'paga' => 'cliente'], $c)['importe']);
        // Mano de obra de la aseguradora con 5 %: 2 h × 500 × 0.95 = 950
        $this->assertSame(950.0, $this->cotizador->calcularPartida(['tipo' => 'mano_obra', 'area_id' => 1, 'horas' => 2, 'paga' => 'aseguradora'], $c)['importe']);

        $t = $this->cotizador->totales([
            ['tipo' => 'refaccion', 'paga' => 'aseguradora', 'importe' => 10000],
            ['tipo' => 'mano_obra', 'paga' => 'cliente', 'importe' => 1000],
        ], $c);

        // Cubierto 10,000 + IVA = 11,600 − deducible 5,000 − demérito 300 − salvamento 200 = 6,100
        $this->assertSame(6100.0, $t['monto_aseguradora']);
        // No cubierto 1,000 + IVA = 1,160 + deducible 5,000 + demérito 300 = 6,460
        $this->assertSame(6460.0, $t['monto_cliente']);
        $this->assertSame(5000.0, $t['deducible']);
        $this->assertSame(12760.0, $t['total']);
    }

    public function test_el_deducible_nunca_deja_negativa_a_la_aseguradora(): void
    {
        $c = $this->contexto(['esSeguro' => true, 'deducible' => 50000]);

        $t = $this->cotizador->totales([['tipo' => 'refaccion', 'paga' => 'aseguradora', 'importe' => 1000]], $c);

        $this->assertSame(0.0, $t['monto_aseguradora']);
        $this->assertSame(1160.0, $t['monto_cliente'], 'El cliente paga como máximo lo que cubriría el seguro');
    }
}
