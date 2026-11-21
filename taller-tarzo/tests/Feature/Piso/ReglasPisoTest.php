<?php

namespace Tests\Feature\Piso;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Actions\Cotizacion\CrearSuplemento;
use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Actions\Ordenes\CambiarEtapa;
use App\Actions\Piso\EntregarVehiculo;
use App\Actions\Piso\GenerarTareas;
use App\Actions\Piso\RegistrarCalidad;
use App\Actions\Piso\RelojTarea;
use App\Enums\EstadoTarea;
use App\Enums\MomentoEvidencia;
use App\Enums\MotivoPausa;
use App\Enums\TipoOrden;
use App\Mail\MensajeCorreo;
use App\Models\Etapa;
use App\Models\Evidencia;
use App\Models\Mensaje;
use App\Models\Tarea;
use App\Support\Checklists;
use App\Support\Media;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ReglasPisoTest extends PisoTestCase
{
    public function test_al_autorizar_se_generan_tareas_sin_duplicar(): void
    {
        $orden = $this->ordenAprobada();

        $tareas = $orden->tareas;
        $this->assertCount(1, $tareas, 'Solo la mano de obra genera tarea; la refacción no');
        $this->assertSame('Cambio de balatas', $tareas[0]->descripcion);
        $this->assertSame(2.0, $tareas[0]->horas_estandar);

        $this->assertSame(0, app(GenerarTareas::class)($orden->cotizacionActual), 'Es idempotente');
    }

    public function test_una_pieza_de_hojalateria_genera_laminado_y_pintura(): void
    {
        $orden = $this->orden();
        $c = app(AbrirCotizacion::class)($orden, $this->asesor);
        $c->partidas()->create(['tipo' => 'hojalateria_pintura', 'descripcion' => 'Facia delantera', 'pieza_id' => $this->pieza()->id,
            'operacion_hyp' => 'reparar', 'nivel_dano' => 'medio', 'tipo_pintura' => 'solida', 'horas_laminado' => 3, 'horas_pintura' => 2, 'paga' => 'cliente']);
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'cliente', [], 'Laura');

        $descripciones = $orden->tareas()->pluck('descripcion')->all();
        $this->assertSame(['Laminado: Facia delantera', 'Pintura: Facia delantera'], $descripciones);
    }

    public function test_el_suplemento_no_duplica_tareas(): void
    {
        $orden = $this->ordenAprobada();
        $c2 = app(CrearSuplemento::class)($orden->cotizacionActual);

        $this->assertSame(0, app(GenerarTareas::class)($c2));
        $this->assertSame(1, $orden->tareas()->count());
        $this->assertSame($c2->partidas()->first()->id, $orden->tareas()->first()->partida_id);
    }

    public function test_refacciones_por_pedir_mandan_el_auto_a_espera(): void
    {
        $orden = $this->orden();
        $c = $this->cotizacionConPartidas($orden);
        $c->partidas()->where('tipo', 'refaccion')->update(['disponibilidad' => 'por_pedir']);
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'cliente', [], 'Laura');

        $this->assertSame(Etapa::ESPERA_REFACCIONES, $orden->fresh()->etapa->clave);
    }

    public function test_sin_cotizacion_autorizada_no_entra_a_mecanica(): void
    {
        $orden = $this->orden();

        $this->assertNotEmpty(CambiarEtapa::problemas($orden, $this->etapa('mecanica')));

        try {
            (new CambiarEtapa)($orden, $this->etapa('mecanica'), $this->jefe);
            $this->fail('Debió bloquearse');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cotización debe estar autorizada', collect($e->errors())->flatten()->first());
        }

        // Con permiso y comentario se puede forzar, y queda marcado.
        (new CambiarEtapa)($orden, $this->etapa('mecanica'), $this->jefe, 'Cliente autorizó en persona', forzar: true);
        $this->assertTrue($orden->historialEtapas()->first()->excepcion);

        (new CambiarEtapa)($this->ordenAprobada(), $this->etapa('mecanica'), $this->jefe);
        $this->addToAssertionCount(1);
    }

    public function test_forzar_exige_comentario(): void
    {
        $this->expectException(ValidationException::class);
        (new CambiarEtapa)($this->orden(), $this->etapa('mecanica'), $this->jefe, null, forzar: true);
    }

    public function test_en_seguro_no_se_avanza_de_hojalateria_sin_evidencia(): void
    {
        $orden = $this->ordenAprobada(TipoOrden::Seguro);
        (new CambiarEtapa)($orden, $this->etapa('hojalateria'), $this->jefe);

        $this->assertNotEmpty(CambiarEtapa::problemas($orden->fresh(), $this->etapa('pintura')));

        Evidencia::create(['orden_servicio_id' => $orden->id, 'etapa_id' => $this->etapa('hojalateria')->id, 'momento' => MomentoEvidencia::Desmontada, 'archivo' => 'x.jpg']);
        $this->assertSame([], CambiarEtapa::problemas($orden->fresh(), $this->etapa('pintura')));
    }

    public function test_limite_de_autos_por_etapa(): void
    {
        $this->etapa('mecanica')->update(['limite_autos' => 1]);
        (new CambiarEtapa)($this->ordenAprobada(), $this->etapa('mecanica'), $this->jefe);

        $this->assertStringContainsString('límite de 1', implode(' ', CambiarEtapa::problemas($this->ordenAprobada(), $this->etapa('mecanica'))));
    }

    public function test_reloj_por_tarea_y_una_sola_tarea_activa(): void
    {
        $tecnico = $this->tecnicoConUsuario();
        $orden = $this->ordenAprobada();
        $t1 = $orden->tareas->first();
        $t2 = Tarea::create(['orden_servicio_id' => $orden->id, 'descripcion' => 'Otra', 'horas_estandar' => 1]);
        $reloj = app(RelojTarea::class);

        $this->travelTo(now()->setTime(9, 0));
        $reloj->iniciar($t1, $tecnico);
        $this->travel(45)->minutes();
        $reloj->pausar($t1->fresh(), MotivoPausa::Comida);
        $this->assertSame(0.75, $t1->fresh()->horas_reales);

        $this->travel(30)->minutes();
        $reloj->iniciar($t1->fresh(), $tecnico);
        $this->travel(15)->minutes();
        $reloj->iniciar($t2, $tecnico); // pausa t1 automáticamente
        $this->assertSame(EstadoTarea::Pausada, $t1->fresh()->estado);
        $this->assertSame(1.0, $t1->fresh()->horas_reales);
        $this->assertSame(MotivoPausa::CambioTarea, $t1->registros()->reorder('id', 'desc')->first()->motivo_pausa);

        $this->travel(60)->minutes();
        $reloj->terminar($t2->fresh());
        $this->assertSame(EstadoTarea::Terminada, $t2->fresh()->estado);
        $this->assertSame(1.0, $t2->fresh()->eficiencia());
    }

    public function test_no_se_inicia_la_tarea_de_otro_tecnico(): void
    {
        $orden = $this->ordenAprobada();
        $tarea = $orden->tareas->first();
        $tarea->update(['tecnico_id' => $this->tecnicoConUsuario()->id]);

        $this->expectException(ValidationException::class);
        app(RelojTarea::class)->iniciar($tarea, $this->tecnicoConUsuario());
    }

    public function test_calidad_aprobada_pasa_a_listo_y_avisa_al_cliente(): void
    {
        Mail::fake();
        $orden = $this->ordenAprobada();
        (new CambiarEtapa)($orden, $this->etapa('calidad'), $this->jefe);

        try {
            app(RegistrarCalidad::class)($orden->fresh(), ['prueba_ruta'], $this->jefe);
            $this->fail('Sin todos los puntos debe pedir a dónde regresa');
        } catch (ValidationException) {
        }

        app(RegistrarCalidad::class)($orden->fresh(), ['prueba_ruta'], $this->jefe, 'Tono no igualado', $this->etapa('pintura'));
        $this->assertSame('pintura', $orden->fresh()->etapa->clave);

        (new CambiarEtapa)($orden->fresh(), $this->etapa('calidad'), $this->jefe);
        app(RegistrarCalidad::class)($orden->fresh(), array_keys(Checklists::CALIDAD), $this->jefe);
        $this->assertSame(Etapa::LISTO_ENTREGA, $orden->fresh()->etapa->clave);
        $aviso = Mensaje::where('orden_servicio_id', $orden->id)->where('evento', 'listo_entrega')->sole();
        $this->assertSame('correo', $aviso->canal);
        $this->assertStringContainsString('está listo', $aviso->cuerpo);
        Mail::assertSent(MensajeCorreo::class, fn ($m) => $m->hasTo('cliente@example.com'));
    }

    public function test_no_se_salta_calidad_para_ir_a_listo(): void
    {
        $orden = $this->ordenAprobada();

        $this->assertStringContainsString('control de calidad', implode(' ', CambiarEtapa::problemas($orden, $this->etapa(Etapa::LISTO_ENTREGA))));
    }

    public function test_entrega_con_firma_cierra_la_orden(): void
    {
        $orden = $this->ordenAprobada();
        $orden->vehiculo->update(['kilometraje' => 50000]);
        (new CambiarEtapa)($orden, $this->etapa('calidad'), $this->jefe);
        app(RegistrarCalidad::class)($orden->fresh(), array_keys(Checklists::CALIDAD), $this->jefe);

        try {
            app(EntregarVehiculo::class)($orden->fresh(), ['explicacion'], $this->firmaPng(), 'Laura', $this->asesor);
            $this->fail('Faltan puntos del checklist');
        } catch (ValidationException) {
        }

        $checklist = app(EntregarVehiculo::class)($orden->fresh(), array_keys(Checklists::ENTREGA), $this->firmaPng(), 'Laura Hernández', $this->asesor, 51000);

        $orden->refresh();
        $this->assertSame(Etapa::ENTREGADO, $orden->etapa->clave);
        $this->assertNotNull($orden->fecha_entrega);
        $this->assertNotNull($orden->cerrada_at);
        $this->assertSame('Laura Hernández', $checklist->firmado_por);
        Media::disco()->assertExists($checklist->firma);
        $this->assertSame(51000, $orden->vehiculo->fresh()->kilometraje);
    }
}
