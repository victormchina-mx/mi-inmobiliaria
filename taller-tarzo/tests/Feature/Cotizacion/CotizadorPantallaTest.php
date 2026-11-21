<?php

namespace Tests\Feature\Cotizacion;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Enums\EstadoCotizacion;
use App\Enums\Rol;
use App\Enums\TipoOrden;
use App\Filament\Resources\Cotizaciones\Pages\EditCotizacion;
use App\Filament\Resources\OrdenServicios\Pages\ViewOrdenServicio;
use App\Filament\Resources\OrdenServicios\RelationManagers\DiagnosticosRelationManager;
use App\Models\Aseguradora;
use App\Models\Cotizacion;
use App\Models\Diagnostico;
use App\Models\Operacion;
use App\Models\Paquete;
use App\Models\Tabulador;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

class CotizadorPantallaTest extends CotizacionTestCase
{
    public function test_cotizar_desde_la_orden_crea_la_primera_version(): void
    {
        $orden = $this->orden();

        Livewire::test(ViewOrdenServicio::class, ['record' => $orden->getRouteKey()])
            ->callAction('cotizar')
            ->assertRedirectContains('/panel/cotizaciones/');

        $c = Cotizacion::first();
        $this->assertSame("{$orden->folio}-C1", $c->folio);
        $this->assertSame(EstadoCotizacion::Borrador, $c->estado);
        $this->assertNull($c->aseguradora_id);
    }

    public function test_captura_partidas_de_los_tres_tipos_y_guarda_importes(): void
    {
        $c = app(AbrirCotizacion::class)($this->orden(), $this->asesor);
        $op = Operacion::firstWhere('nombre', 'Cambio de balatas delanteras');

        Livewire::test(EditCotizacion::class, ['record' => $c->getRouteKey()])
            ->fillForm([
                'vigencia_dias' => 10,
                'partidas' => [
                    ['tipo' => 'mano_obra', 'descripcion' => $op->nombre, 'area_id' => $op->area_id, 'horas' => 1.2],
                    ['tipo' => 'refaccion', 'descripcion' => 'Balatas', 'cantidad' => 2, 'costo_unitario' => 500, 'calidad' => 'original'],
                    ['tipo' => 'hojalateria_pintura', 'descripcion' => 'Facia delantera', 'pieza_id' => $this->pieza()->id,
                        'operacion_hyp' => 'reparar', 'nivel_dano' => 'medio', 'tipo_pintura' => 'solida',
                        'horas_laminado' => 2, 'horas_pintura' => 2, 'costo_unitario' => 9999],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $c->refresh();
        $partidas = $c->partidas;
        $this->assertSame(600.0, $partidas[0]->importe);   // 1.2 h × 500
        $this->assertSame(1300.0, $partidas[1]->importe);  // 2 × 500 × 1.3
        // 2 × 450 + 2 × 480 + 1.1 m² × 400 × 0.7 = 900 + 960 + 308 = 2,168
        $this->assertSame(308.0, $partidas[2]->importe_materiales);
        $this->assertSame(2168.0, $partidas[2]->importe);
        $this->assertNull($partidas[2]->costo_unitario, 'Al reparar no se guarda costo de pieza nueva');
        $this->assertNull($partidas[1]->pieza_id);

        // 600 + 1,300 + 2,168 + cabina 800 = 4,868; + IVA = 5,646.88
        $this->assertSame(1, $c->ciclos_cabina);
        $this->assertSame(4868.0, $c->subtotal);
        $this->assertSame(5646.88, $c->total);
        $this->assertSame(10, $c->vigencia_dias);
    }

    public function test_orden_de_seguro_usa_tabulador_y_deducible(): void
    {
        $qualitas = Aseguradora::firstWhere('nombre', 'Qualitas');
        $tab = Tabulador::create(['aseguradora_id' => $qualitas->id, 'descuento_refacciones' => 10]);
        $tab->tarifas()->create(['area_id' => $this->mecanica->id, 'tarifa_hora' => 400]);

        $orden = $this->orden(TipoOrden::Seguro);
        $orden->siniestro()->create(['aseguradora_id' => $qualitas->id, 'numero' => '04-111', 'deducible_tipo' => 'monto', 'deducible_valor' => 1000]);

        $c = $this->cotizacionConPartidas($orden, paga: 'aseguradora');

        $this->assertSame($qualitas->id, $c->aseguradora_id);
        $this->assertSame(800.0, $c->partidas[0]->importe);  // 2 h × 400 (tarifa pactada)
        $this->assertSame(1170.0, $c->partidas[1]->importe); // 1,300 × 0.90
        // (800 + 1,170) × 1.16 = 2,285.20 − deducible 1,000
        $this->assertSame(1285.2, $c->monto_aseguradora);
        $this->assertSame(1000.0, $c->monto_cliente);
    }

    public function test_paquete_y_diagnosticos_se_convierten_en_partidas(): void
    {
        $orden = $this->orden();
        $c = app(AbrirCotizacion::class)($orden, $this->asesor);

        Livewire::test(EditCotizacion::class, ['record' => $c->getRouteKey()])
            ->callAction('paquete', data: ['paquete_id' => Paquete::first()->id])
            ->assertHasNoActionErrors();
        $this->assertSame(6, $c->partidas()->count());

        $d1 = Diagnostico::create(['orden_servicio_id' => $orden->id, 'hallazgo' => 'Fuga de aceite', 'prioridad' => 'recomendado']);
        $d2 = Diagnostico::create(['orden_servicio_id' => $orden->id, 'hallazgo' => 'Balatas al 10%', 'prioridad' => 'urgente']);

        Livewire::test(DiagnosticosRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => ViewOrdenServicio::class])
            ->selectTableRecords([$d1->id, $d2->id])
            ->callAction(TestAction::make('aCotizacion')->table()->bulk())
            ->assertHasNoActionErrors();

        $nuevas = $c->partidas()->whereNotNull('diagnostico_id')->get();
        $this->assertCount(2, $nuevas);
        $this->assertSame('Balatas al 10% (urgente)', $nuevas->first()->descripcion, 'Lo urgente va primero');
        $this->assertTrue($d1->fresh()->convertido_a_partida);
        $this->assertSame($this->mecanica->id, $nuevas->first()->area_id, 'Sin técnico, se asigna Mecánica');
        $this->assertSame(500.0, $nuevas->first()->importe);
    }

    public function test_una_cotizacion_enviada_no_se_puede_editar(): void
    {
        $c = $this->cotizacionConPartidas();
        $c->update(['estado' => EstadoCotizacion::Enviada]);

        Livewire::test(EditCotizacion::class, ['record' => $c->getRouteKey()])
            ->assertFormFieldDisabled('vigencia_dias')
            ->assertDontSee('Guardar y recalcular')
            ->assertSee('Registrar autorización');
    }

    public function test_permisos_de_cotizacion(): void
    {
        $c = $this->cotizacionConPartidas();

        $this->actingAs($this->usuario(Rol::Tecnico));
        $this->get('/panel/cotizaciones')->assertForbidden();
        $this->get(route('cotizaciones.pdf', $c))->assertForbidden();

        $this->actingAs($this->usuario(Rol::Valuador));
        $this->get('/panel/cotizaciones')->assertOk();
        $this->get(route('cotizaciones.pdf', $c))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('cotizaciones.pdf', [$c, 'formato' => 'valuacion']))->assertOk();
    }
}
