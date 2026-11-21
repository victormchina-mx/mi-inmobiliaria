<?php

namespace Tests\Feature\Comunicacion;

use App\Actions\Cotizacion\EnviarCotizacion;
use App\Enums\CanalPreferido;
use App\Enums\EstadoMensaje;
use App\Enums\EventoMensaje;
use App\Enums\Rol;
use App\Filament\Pages\Conversaciones;
use App\Mail\MensajeCorreo;
use App\Models\Mensaje;
use App\Services\Comunicacion\Notificador;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\Feature\Piso\PisoTestCase;

class MensajesTest extends PisoTestCase
{
    private function activarWhatsApp(): void
    {
        config(['tarzo.whatsapp' => [...config('tarzo.whatsapp'), 'driver' => 'cloud', 'token' => 't', 'phone_number_id' => '123', 'app_secret' => 'secreto', 'verify_token' => 'verifica']]);
    }

    public function test_sin_whatsapp_avisa_por_correo_y_queda_en_bitacora(): void
    {
        Mail::fake();
        $orden = $this->orden();

        $m = app(Notificador::class)->avisar(EventoMensaje::ListoEntrega, $orden->cliente, $orden);

        $this->assertSame('correo', $m->canal);
        $this->assertSame(EstadoMensaje::Enviado, $m->fresh()->estado);
        $this->assertStringContainsString($orden->vehiculo->marca, $m->cuerpo);
        Mail::assertSent(MensajeCorreo::class, fn ($mail) => $mail->hasTo('cliente@example.com'));
    }

    public function test_con_whatsapp_envia_la_plantilla_aprobada_con_sus_variables(): void
    {
        $this->activarWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]])]);
        $orden = $this->orden();

        $m = app(Notificador::class)->avisar(EventoMensaje::ListoEntrega, $orden->cliente, $orden);

        $this->assertSame('whatsapp', $m->canal);
        $this->assertSame('527221234567', $m->destino);
        $this->assertSame('wamid.ABC', $m->fresh()->proveedor_id);
        Http::assertSent(fn ($r) => $r['type'] === 'template' && $r['template']['name'] === 'tarzo_listo'
            && $r['template']['components'][0]['parameters'][0]['text'] === $orden->cliente->nombre
            && $r['to'] === '527221234567');
    }

    public function test_respeta_preferencia_de_llamada_y_consentimiento(): void
    {
        Mail::fake();
        $orden = $this->orden();

        $orden->cliente->update(['acepta_recordatorios' => false]);
        $encuesta = app(Notificador::class)->avisar(EventoMensaje::EncuestaNps, $orden->cliente->fresh(), $orden);
        $this->assertSame(EstadoMensaje::Omitido, $encuesta->estado);
        $avance = app(Notificador::class)->avisar(EventoMensaje::CambioEtapa, $orden->cliente->fresh(), $orden);
        $this->assertSame(EstadoMensaje::Enviado, $avance->fresh()->estado, 'Los avisos de la orden no dependen del consentimiento de marketing');

        $orden->cliente->update(['canal_preferido' => CanalPreferido::Llamada]);
        $m = app(Notificador::class)->avisar(EventoMensaje::ListoEntrega, $orden->cliente->fresh(), $orden);
        $this->assertSame(EstadoMensaje::Omitido, $m->estado);
        $this->assertStringContainsString('llamada', $m->error);
    }

    public function test_un_error_de_whatsapp_deja_el_mensaje_fallido_y_se_puede_reintentar(): void
    {
        $this->activarWhatsApp();
        Http::fakeSequence('graph.facebook.com/*')
            ->push(['error' => ['message' => 'Número inválido']], 400)
            ->push(['messages' => [['id' => 'wamid.OK']]]);
        $orden = $this->orden();

        try {
            app(Notificador::class)->avisar(EventoMensaje::ListoEntrega, $orden->cliente, $orden);
        } catch (RuntimeException) {
        }

        $m = Mensaje::latest('id')->first();
        $this->assertSame(EstadoMensaje::Fallido, $m->estado);
        $this->assertStringContainsString('Número inválido', $m->error);

        Notificador::reintentar($m);
        $this->assertSame(EstadoMensaje::Enviado, $m->fresh()->estado);
        $this->assertSame(2, $m->fresh()->intentos);
    }

    public function test_presupuesto_listo_sale_automatico_con_total_y_enlace(): void
    {
        Mail::fake();
        $orden = $this->orden();
        $c = $this->cotizacionConPartidas($orden);

        app(EnviarCotizacion::class)($c, ['whatsapp_api']);

        $m = Mensaje::where('evento', 'presupuesto_listo')->sole();
        $this->assertStringContainsString('/presupuesto/', $m->cuerpo);
        $this->assertMatchesRegularExpression('/\$\s?\d/', $m->cuerpo);
    }

    public function test_webhook_verifica_token_y_rechaza_firmas_invalidas(): void
    {
        $this->activarWhatsApp();

        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=verifica&hub_challenge=42')->assertOk()->assertSee('42');
        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=otro&hub_challenge=42')->assertForbidden();
        $this->postJson('/webhooks/whatsapp', ['entry' => []], ['X-Hub-Signature-256' => 'sha256=falsa'])->assertForbidden();
    }

    public function test_webhook_actualiza_estados_y_guarda_mensajes_del_cliente(): void
    {
        $this->activarWhatsApp();
        $orden = $this->orden();
        $enviado = Mensaje::create(['cliente_id' => $orden->cliente_id, 'canal' => 'whatsapp', 'cuerpo' => 'x', 'estado' => EstadoMensaje::Enviado, 'proveedor_id' => 'wamid.1']);

        $this->webhook(['statuses' => [['id' => 'wamid.1', 'status' => 'read', 'timestamp' => (string) now()->timestamp]]])->assertOk();
        $this->assertSame(EstadoMensaje::Leido, $enviado->fresh()->estado);

        $carga = ['contacts' => [['profile' => ['name' => 'Laura']]], 'messages' => [['id' => 'wamid.2', 'from' => '5217221234567', 'type' => 'text', 'text' => ['body' => '¿Ya está mi carro?']]]];
        $this->webhook($carga)->assertOk();
        $this->webhook($carga)->assertOk(); // Meta reintenta: no se duplica

        $entrante = Mensaje::where('direccion', 'entrante')->sole();
        $this->assertSame($orden->cliente_id, $entrante->cliente_id, 'Se reconoce al cliente por los últimos 10 dígitos');
        $this->assertSame($orden->id, $entrante->orden_servicio_id);
        $this->assertTrue(Notificador::ventanaAbierta($orden->cliente));
        $this->assertSame(1, $this->asesor->notifications()->count(), 'Avisa al asesor de la orden');
    }

    public function test_el_asesor_responde_desde_la_bandeja_solo_dentro_de_24_horas(): void
    {
        $this->activarWhatsApp();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.R']]])]);
        $orden = $this->orden();
        $entrante = Mensaje::create(['direccion' => 'entrante', 'cliente_id' => $orden->cliente_id, 'canal' => 'whatsapp', 'cuerpo' => 'Hola', 'estado' => EstadoMensaje::Recibido]);

        $this->actingAs($this->asesor);
        Livewire::test(Conversaciones::class, ['cliente' => $orden->cliente_id])
            ->assertSee('Hola')
            ->set('texto', 'Mañana a las 5 queda listo')
            ->call('enviar')
            ->assertSet('texto', '');

        $respuesta = Mensaje::where('evento', 'respuesta')->sole();
        $this->assertSame($this->asesor->id, $respuesta->user_id);
        $this->assertNotNull($entrante->fresh()->leido_at);
        Http::assertSent(fn ($r) => $r['type'] === 'text' && $r['text']['body'] === 'Mañana a las 5 queda listo');

        $entrante->forceFill(['created_at' => now()->subHours(25)])->save();
        Livewire::test(Conversaciones::class, ['cliente' => $orden->cliente_id])
            ->set('texto', 'Otro')->call('enviar')->assertNotified();
        $this->assertSame(1, Mensaje::where('evento', 'respuesta')->count());
    }

    public function test_pantallas_de_postventa_por_rol(): void
    {
        $this->actingAs($this->asesor);
        foreach (['conversaciones', 'satisfaccion', 'mensajes', 'recordatorios', 'garantias', 'plantillas-mensaje', 'reglas-mantenimiento'] as $ruta) {
            $this->get("/panel/{$ruta}")->assertOk();
        }

        $this->actingAs($this->usuario(Rol::Tecnico));
        $this->get('/panel/conversaciones')->assertForbidden();
        $this->get('/panel/mensajes')->assertForbidden();
    }

    private function webhook(array $valor)
    {
        $cuerpo = json_encode(['entry' => [['changes' => [['value' => $valor]]]]]);

        return $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $cuerpo, 'secreto'),
        ], $cuerpo);
    }
}
