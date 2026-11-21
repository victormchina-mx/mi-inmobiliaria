<?php

namespace Tests\Feature\Comunicacion;

use App\Actions\Postventa\CalendarioVerificacion;
use App\Actions\Postventa\EnviarEncuestas;
use App\Actions\Postventa\EnviarRecordatorios;
use App\Actions\Postventa\GenerarGarantias;
use App\Actions\Postventa\GenerarRecordatorios;
use App\Actions\Postventa\ReclamarGarantia;
use App\Actions\Postventa\RevisarGarantias;
use App\Enums\Rol;
use App\Enums\TipoGarantia;
use App\Enums\TipoOrden;
use App\Filament\Pages\Satisfaccion;
use App\Models\EncuestaNps;
use App\Models\Garantia;
use App\Models\OrdenServicio;
use App\Models\Recordatorio;
use App\Support\EnlacePortal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Feature\Piso\PisoTestCase;

class PostventaTest extends PisoTestCase
{
    private function entregada(Carbon $cuando, ?int $km = 50000, ?string $placas = 'MXA-123-5'): OrdenServicio
    {
        $orden = $this->ordenAprobada();
        $orden->vehiculo->update(['placas' => $placas, 'kilometraje' => $km]);
        $orden->forceFill(['fecha_entrega' => $cuando, 'cerrada_at' => $cuando, 'kilometraje_ingreso' => $km])->save();

        return $orden->fresh();
    }

    public function test_al_entregar_se_generan_garantias_por_tipo_de_trabajo(): void
    {
        $orden = $this->entregada(now());

        $this->assertSame(2, (new GenerarGarantias)($orden));
        $this->assertSame(0, (new GenerarGarantias)($orden), 'Es idempotente');

        $mecanica = Garantia::where('tipo', TipoGarantia::Mecanica->value)->sole();
        $this->assertTrue($mecanica->vence->equalTo(today()->addMonthsNoOverflow(3)));
        $this->assertSame(55000, $mecanica->km_limite, '3 meses o 5,000 km');
        $this->assertTrue(Garantia::where('tipo', TipoGarantia::Refacciones->value)->exists());
    }

    public function test_revision_diaria_vence_y_avisa_garantias(): void
    {
        $orden = $this->entregada(now());
        (new GenerarGarantias)($orden);
        $g = Garantia::first();

        $antes = $this->asesor->notifications()->count();
        $g->update(['vence' => today()->addDays(10)]);
        $this->assertSame(1, (new RevisarGarantias)());
        $this->assertSame(0, (new RevisarGarantias)(), 'Avisa una sola vez');
        $this->assertSame($antes + 1, $this->asesor->notifications()->count());

        $g->update(['vence' => today()->subDay()]);
        (new RevisarGarantias)();
        $this->assertSame('vencida', $g->fresh()->estado);
    }

    public function test_reclamar_garantia_abre_orden_de_garantia(): void
    {
        $orden = $this->entregada(now());
        (new GenerarGarantias)($orden);
        $g = Garantia::first();

        $nueva = (new ReclamarGarantia)($g, 'Ruido en suspensión', $this->asesor);

        $this->assertSame(TipoOrden::Garantia, $nueva->tipo);
        $this->assertSame($orden->vehiculo_id, $nueva->vehiculo_id);
        $this->assertSame('reclamada', $g->fresh()->estado);
        $this->assertStringContainsString($orden->folio, $nueva->motivo_ingreso);
    }

    public function test_calendario_de_verificacion_por_terminacion_de_placa(): void
    {
        $this->assertSame(5, CalendarioVerificacion::digito('MXA-123-5'));
        $this->assertSame(0, CalendarioVerificacion::digito('ABC-0A0'));
        $this->assertNull(CalendarioVerificacion::digito('SIN-PLACA'));
        $this->assertSame('amarillo', CalendarioVerificacion::periodoQueIniciaEn('MXA-123-5', 7)['engomado']);
        $this->assertSame(2, CalendarioVerificacion::periodoQueIniciaEn('MXA-123-5', 7)['periodo']);
        $this->assertNull(CalendarioVerificacion::periodoQueIniciaEn('MXA-123-5', 3));
        $this->assertSame(['azul', 5, 11], CalendarioVerificacion::CALENDARIO[9]);
    }

    public function test_programa_y_envia_recordatorios_de_servicio_y_verificacion(): void
    {
        Mail::fake();
        Carbon::setTestNow('2027-01-15 10:00');
        $this->entregada(Carbon::parse('2026-07-10'));

        // Enero: 6 meses después (aceite / rotación) y placa 5 verifica en enero-febrero.
        $this->assertGreaterThanOrEqual(3, (new GenerarRecordatorios)(Carbon::parse('2027-01-01')));
        $this->assertSame(0, (new GenerarRecordatorios)(Carbon::parse('2027-01-01')), 'No duplica');

        $aceite = Recordatorio::where('titulo', 'Cambio de aceite y filtro')->sole();
        $this->assertSame('2027-01-10', $aceite->fecha->toDateString());
        $this->assertTrue(Recordatorio::where('tipo', 'verificacion')->where('titulo', 'like', '%amarillo%')->exists());

        $enviados = (new EnviarRecordatorios)();
        $this->assertSame(Recordatorio::count(), $enviados);
        $this->assertSame('enviado', $aceite->fresh()->estado);
        $this->assertStringContainsString('Cambio de aceite', $aceite->fresh()->mensaje->cuerpo);
        Carbon::setTestNow();
    }

    public function test_estima_el_uso_diario_con_el_historial(): void
    {
        $orden = $this->entregada(now()->subDays(100), 50000);
        OrdenServicio::whereKey($orden->id)->update(['fecha_entrega' => now()->subDays(100)]);
        $segunda = $this->orden();
        $segunda->update(['vehiculo_id' => $orden->vehiculo_id]);
        $segunda->forceFill(['fecha_entrega' => now(), 'cerrada_at' => now(), 'kilometraje_ingreso' => 56000])->save();

        $v = $orden->vehiculo->load(['ordenes' => fn ($q) => $q->whereNotNull('fecha_entrega')->orderByDesc('fecha_entrega')]);
        $this->assertEqualsWithDelta(60, (new GenerarRecordatorios)->kmPorDia($v), 0.01);
    }

    public function test_no_recuerda_si_el_auto_esta_en_el_taller(): void
    {
        Mail::fake();
        $orden = $this->entregada(now()->subYear());
        $r = Recordatorio::create(['vehiculo_id' => $orden->vehiculo_id, 'cliente_id' => $orden->cliente_id, 'tipo' => 'servicio', 'titulo' => 'Afinación', 'fecha' => today(), 'referencia' => 'x']);
        $this->orden()->update(['vehiculo_id' => $orden->vehiculo_id]); // nueva orden abierta

        $this->assertSame(0, (new EnviarRecordatorios)());
        $this->assertSame('programado', $r->fresh()->estado);
    }

    public function test_encuesta_nps_un_dia_despues_y_detractor_avisa_a_gerencia(): void
    {
        Mail::fake();
        $orden = $this->entregada(now()->subHours(30));
        $this->entregada(now()->subHours(2)); // aún no

        $this->assertSame(1, (new EnviarEncuestas)());
        $this->assertSame(0, (new EnviarEncuestas)(), 'Una por orden');
        $encuesta = EncuestaNps::sole();
        $this->assertSame($orden->id, $encuesta->orden_servicio_id);

        $enlace = EnviarEncuestas::enlace($encuesta);
        $this->get($enlace)->assertOk()->assertSee('recomiendes');
        $this->get('/encuesta/'.$encuesta->id)->assertForbidden();

        $antes = $this->asesor->notifications()->count();
        $this->post($enlace, ['calificacion' => 4, 'comentario' => 'Tardaron mucho'])->assertRedirect();
        $this->assertSame(4, $encuesta->fresh()->calificacion);
        $this->assertSame($antes + 1, $this->asesor->notifications()->count(), 'El asesor tiene crm.gestionar');

        $this->post($enlace, ['calificacion' => 10])->assertSessionHasErrors('calificacion');
        $this->get($enlace)->assertSee('Gracias');
    }

    public function test_calculo_nps_y_pantalla_de_seguimiento(): void
    {
        $this->assertSame(25, EncuestaNps::calcular([10, 9, 8, 3]));
        $this->assertSame(-100, EncuestaNps::calcular([0, 6]));
        $this->assertNull(EncuestaNps::calcular([null]));

        $orden = $this->entregada(now()->subDays(2));
        $e = EncuestaNps::create(['orden_servicio_id' => $orden->id, 'cliente_id' => $orden->cliente_id, 'enviada_at' => now(), 'calificacion' => 5, 'respondida_at' => now()]);

        $this->actingAs($this->asesor);
        Livewire::test(Satisfaccion::class)
            ->assertSee('-100')
            ->callTableAction('seguimiento', $e, ['seguimiento_notas' => 'Se le ofreció revisión sin costo'])
            ->assertHasNoTableActionErrors();
        $this->assertSame($this->asesor->id, $e->fresh()->seguimiento_user_id);
    }

    public function test_portal_de_seguimiento_y_baja_de_recordatorios(): void
    {
        $orden = $this->ordenAprobada();

        $this->get(EnlacePortal::generar($orden))->assertOk()->assertSee($orden->folio)->assertSee($orden->vehiculo->marca);
        $this->get('/seguimiento/'.$orden->id)->assertForbidden();

        $baja = url(\URL::signedRoute('baja.recordatorios', ['cliente' => $orden->cliente_id], absolute: false));
        $this->get($baja)->assertOk();
        $this->assertFalse($orden->cliente->fresh()->acepta_recordatorios);
    }

    public function test_entrega_completa_genera_garantias_y_aviso(): void
    {
        $this->actingAs($this->usuario(Rol::Dueno));
        $this->artisan('tarzo:revisar-garantias')->assertSuccessful();
        $this->artisan('tarzo:generar-recordatorios', ['--mes' => '2027-01'])->assertSuccessful();
        $this->artisan('tarzo:enviar-recordatorios')->assertSuccessful();
        $this->artisan('tarzo:enviar-encuestas')->assertSuccessful();
    }
}
