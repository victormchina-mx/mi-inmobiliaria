<?php

namespace Tests\Feature\Cotizacion;

use App\Actions\Cotizacion\AbrirCotizacion;
use App\Actions\Cotizacion\CrearSuplemento;
use App\Actions\Cotizacion\EnlaceAprobacion;
use App\Actions\Cotizacion\EnviarCotizacion;
use App\Actions\Cotizacion\ResponderCotizacion;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPartida;
use App\Enums\TipoOrden;
use App\Filament\Resources\Cotizaciones\Pages\EditCotizacion;
use App\Mail\PresupuestoParaAutorizar;
use App\Models\Aseguradora;
use App\Models\Cotizacion;
use App\Services\Cotizacion\Cotizador;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

class AutorizacionTest extends CotizacionTestCase
{
    public function test_enviar_marca_la_cotizacion_y_manda_correo(): void
    {
        Mail::fake();
        $c = $this->cotizacionConPartidas();

        app(EnviarCotizacion::class)($c, ['cliente']);

        $this->assertSame(EstadoCotizacion::Enviada, $c->fresh()->estado);
        $this->assertNotNull($c->fresh()->enviada_at);
        Mail::assertQueued(PresupuestoParaAutorizar::class, fn ($m) => $m->hasTo('cliente@example.com') && $m->rol === 'cliente');
    }

    public function test_no_se_envia_una_cotizacion_vacia(): void
    {
        $c = app(AbrirCotizacion::class)($this->orden(), $this->asesor);

        $this->expectException(ValidationException::class);
        app(EnviarCotizacion::class)($c);
    }

    public function test_el_cliente_autoriza_parcialmente_desde_el_enlace(): void
    {
        $c = $this->cotizacionConPartidas();
        app(EnviarCotizacion::class)($c);
        [$mo, $ref] = $c->partidas;
        auth()->logout();

        $enlace = EnlaceAprobacion::generar($c, 'cliente');
        $this->get($enlace)->assertOk()->assertSee('Cambio de balatas')->assertSee('Balatas delanteras')->assertSee('Enviar respuesta');

        $this->post($enlace, [
            'nombre' => 'Laura Hernández',
            'decision' => [$mo->id => 'aprobada', $ref->id => 'rechazada'],
            'comentario' => 'Traigo mis propias balatas',
            'acepta' => '1',
        ])->assertRedirect($enlace);

        $c->refresh();
        $this->assertSame(EstadoCotizacion::AprobadaParcial, $c->estado);
        $this->assertSame(EstadoPartida::Aprobada, $mo->fresh()->estado);
        $this->assertSame(EstadoPartida::Rechazada, $ref->fresh()->estado);
        // Las rechazadas no suman: 1,000 + IVA
        $this->assertSame(1160.0, $c->total);

        $aprob = $c->aprobaciones()->first();
        $this->assertSame('enlace', $aprob->canal);
        $this->assertSame(64, strlen($aprob->huella));
        $this->assertSame('Traigo mis propias balatas', $aprob->comentario);

        // El asesor recibe aviso en el panel.
        $this->assertSame(1, $this->asesor->notifications()->count());
        $this->assertStringContainsString($c->folio, $this->asesor->notifications()->first()->data['title']);

        // El enlace ya no muestra el formulario.
        $this->get($enlace)->assertOk()->assertDontSee('Enviar respuesta')->assertSee('Rechazada');
    }

    public function test_la_cabina_aparece_en_el_detalle_para_que_cuadre_el_subtotal(): void
    {
        $c = $this->cotizacionConPartidas();
        $c->partidas()->create(['tipo' => 'hojalateria_pintura', 'descripcion' => 'Facia', 'pieza_id' => $this->pieza()->id, 'operacion_hyp' => 'pintar', 'tipo_pintura' => 'solida', 'horas_pintura' => 1, 'paga' => 'cliente', 'orden' => 3]);
        app(Cotizador::class)->recalcular($c);
        auth()->logout();

        $this->get(EnlaceAprobacion::generar($c->fresh()))->assertOk()->assertSee('Cabina de pintura (1 ciclo de horneado)')->assertSee('$800.00');
    }

    public function test_en_seguro_ajustador_y_cliente_autorizan_cada_quien_lo_suyo(): void
    {
        $orden = $this->orden(TipoOrden::Seguro);
        $orden->siniestro()->create(['aseguradora_id' => Aseguradora::first()->id, 'numero' => '04-222', 'ajustador_nombre' => 'Ing. Ramírez', 'ajustador_telefono' => '5512345678']);
        $c = $this->cotizacionConPartidas($orden, paga: 'aseguradora');
        $c->partidas()->create(['tipo' => 'otro', 'descripcion' => 'Pulido general', 'cantidad' => 1, 'precio_manual' => 600, 'paga' => 'cliente', 'orden' => 3]);
        app(EnviarCotizacion::class)($c);

        $this->assertCount(2, ResponderCotizacion::partidasDelRol($c, 'ajustador'));
        $this->assertCount(1, ResponderCotizacion::partidasDelRol($c, 'cliente'));
        $this->assertStringStartsWith('https://wa.me/525512345678', EnlaceAprobacion::whatsapp($c, 'ajustador'));

        app(ResponderCotizacion::class)($c, 'ajustador', [], 'Ing. Ramírez');
        $this->assertSame(EstadoCotizacion::Enviada, $c->fresh()->estado, 'Falta la respuesta del cliente');

        app(ResponderCotizacion::class)($c->fresh(), 'cliente', [], 'Laura');
        $this->assertSame(EstadoCotizacion::Aprobada, $c->fresh()->estado);
        $this->assertNotNull($c->fresh()->respondida_at);
    }

    public function test_el_asesor_registra_una_autorizacion_telefonica(): void
    {
        $c = $this->cotizacionConPartidas();
        app(EnviarCotizacion::class)($c);
        [$mo] = $c->partidas;

        Livewire::test(EditCotizacion::class, ['record' => $c->getRouteKey()])
            ->callAction('registrar', data: ['rol' => 'cliente', 'nombre' => 'Laura por teléfono', 'aprobadas' => [$mo->id]])
            ->assertHasNoActionErrors();

        $c->refresh();
        $this->assertSame(EstadoCotizacion::AprobadaParcial, $c->estado);
        $aprob = $c->aprobaciones()->first();
        $this->assertSame('asesor', $aprob->canal);
        $this->assertSame($this->asesor->id, $aprob->user_id);
        $this->assertSame(0, $this->asesor->notifications()->count(), 'No se avisa a quien la capturó');
    }

    public function test_no_se_puede_responder_dos_veces(): void
    {
        $c = $this->cotizacionConPartidas();
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'cliente', [], 'Laura');

        $this->expectException(ValidationException::class);
        app(ResponderCotizacion::class)($c->fresh(), 'cliente', [], 'Laura');
    }

    public function test_el_enlace_vencido_o_alterado_no_funciona(): void
    {
        $c = $this->cotizacionConPartidas();
        auth()->logout();

        $vencido = URL::temporarySignedRoute('presupuesto.mostrar', now()->subMinute(), ['cotizacion' => $c->id, 'rol' => 'cliente'], absolute: false);
        $this->get($vencido)->assertForbidden();

        $alterado = str_replace('/cliente?', '/ajustador?', EnlaceAprobacion::generar($c, 'cliente'));
        $this->get($alterado)->assertForbidden();
    }

    public function test_suplemento_conserva_lo_autorizado_y_solo_pide_lo_nuevo(): void
    {
        $c = $this->cotizacionConPartidas();
        app(EnviarCotizacion::class)($c);
        app(ResponderCotizacion::class)($c, 'cliente', [], 'Laura');
        $this->assertSame(EstadoCotizacion::Aprobada, $c->fresh()->estado);

        $c2 = app(CrearSuplemento::class)($c->fresh(), $this->asesor);

        $this->assertSame(2, $c2->version);
        $this->assertStringEndsWith('-C2', $c2->folio);
        $this->assertSame(EstadoCotizacion::Reemplazada, $c->fresh()->estado);
        $this->assertSame(2, $c2->partidas()->where('estado', 'aprobada')->count());

        $c2->partidas()->create(['tipo' => 'refaccion', 'descripcion' => 'Disco delantero', 'cantidad' => 2, 'costo_unitario' => 700, 'paga' => 'cliente', 'orden' => 3]);
        app(EnviarCotizacion::class)($c2);
        $this->assertSame(1, $c2->partidasPendientes()->count());

        // En pantalla, las partidas ya aprobadas quedan bloqueadas.
        $c2->update(['estado' => EstadoCotizacion::Borrador]);
        Livewire::test(EditCotizacion::class, ['record' => $c2->getRouteKey()])
            ->assertSee('Partida aprobada en una versión anterior');
    }

    public function test_no_se_crea_suplemento_de_un_borrador_ni_de_una_version_vieja(): void
    {
        $c = $this->cotizacionConPartidas();

        try {
            app(CrearSuplemento::class)($c);
            $this->fail('Debió rechazar el borrador');
        } catch (ValidationException) {
        }

        app(EnviarCotizacion::class)($c);
        app(CrearSuplemento::class)($c->fresh());

        $this->expectException(ValidationException::class);
        app(CrearSuplemento::class)(Cotizacion::where('version', 1)->first());
    }

    public function test_recordatorio_a_las_24_horas_una_sola_vez(): void
    {
        Mail::fake();
        $c = $this->cotizacionConPartidas();
        app(EnviarCotizacion::class)($c);
        $c->forceFill(['enviada_at' => now()->subHours(25)])->save();

        $this->artisan('tarzo:recordar-cotizaciones')->assertSuccessful();
        $this->artisan('tarzo:recordar-cotizaciones')->assertSuccessful();

        $this->assertNotNull($c->fresh()->recordatorio_at);
        $this->assertSame(1, $this->asesor->notifications()->count());
        Mail::assertQueued(PresupuestoParaAutorizar::class, fn ($m) => $m->esRecordatorio);
        Mail::assertQueuedCount(1);
    }

    public function test_el_correo_lleva_el_pdf_adjunto(): void
    {
        $c = $this->cotizacionConPartidas();
        $mail = new PresupuestoParaAutorizar($c, 'cliente');

        $adjuntos = $mail->attachments();
        $this->assertCount(1, $adjuntos);
        $this->assertSame("{$c->folio}.pdf", $adjuntos[0]->as);
        $this->assertStringContainsString('/presupuesto/', $mail->render());
    }
}
