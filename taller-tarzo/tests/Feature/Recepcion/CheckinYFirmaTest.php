<?php

namespace Tests\Feature\Recepcion;

use App\Actions\Ordenes\AbrirOrden;
use App\Actions\Recepcion\EnlaceFirma;
use App\Actions\Recepcion\FirmarCheckin;
use App\Enums\Rol;
use App\Enums\TipoOrden;
use App\Filament\Resources\Checkins\CheckinResource;
use App\Filament\Resources\Checkins\Pages\EditCheckin;
use App\Jobs\GenerarPdfIngreso;
use App\Mail\ComprobanteIngreso;
use App\Models\Checkin;
use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use App\Support\Media;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

class CheckinYFirmaTest extends RecepcionTestCase
{
    private OrdenServicio $orden;

    private Checkin $checkin;

    protected function setUp(): void
    {
        parent::setUp();
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 40000]);
        $vehiculo->cliente->update(['email' => 'cliente@example.com', 'telefono' => '7221234567', 'whatsapp' => null]);
        $this->orden = (new AbrirOrden)($vehiculo, TipoOrden::Particular, $this->asesor, ['motivo_ingreso' => 'Servicio']);
        CheckinResource::urlParaOrden($this->orden);
        $this->checkin = $this->orden->fresh()->checkin;
    }

    private function llenarInventario(): array
    {
        return [
            'nivel_combustible' => 4,
            'kilometraje' => 52000,
            'testigos' => ['check_engine'],
            'inventario' => ['gato', 'llanta_refaccion'],
            'pertenencias' => 'Lentes en la guantera',
        ];
    }

    public function test_guarda_el_inventario_con_danos(): void
    {
        Livewire::test(EditCheckin::class, ['record' => $this->checkin->getRouteKey()])
            ->fillForm([
                ...$this->llenarInventario(),
                'danos' => [
                    ['zona' => 'facia_delantera', 'tipo' => 'rayon', 'descripcion' => 'Rayón lado izquierdo'],
                    ['zona' => 'puerta_del_der', 'tipo' => 'golpe'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $c = $this->checkin->fresh('danos');
        $this->assertSame(4, $c->nivel_combustible);
        $this->assertSame(['check_engine'], $c->testigos);
        $this->assertCount(2, $c->danos);
        $this->assertNotNull($c->completado_at);
        $this->assertContains('Triángulos', $c->inventarioFaltante());
        $this->assertFalse($c->estaFirmado());

        // El kilometraje del check-in actualiza la orden y el vehículo.
        $this->assertSame(52000, $this->orden->fresh()->kilometraje_ingreso);
        $this->assertSame(52000, $this->orden->vehiculo->fresh()->kilometraje);
    }

    public function test_el_nombre_del_cliente_se_precarga_y_es_obligatorio_para_firmar(): void
    {
        Livewire::test(EditCheckin::class, ['record' => $this->checkin->getRouteKey()])
            ->assertSchemaStateSet(['firma_nombre' => $this->orden->cliente->nombre_completo]);

        $this->expectException(ValidationException::class);
        app(FirmarCheckin::class)($this->checkin, $this->firmaPng(), '  ', true, true, 'tableta');
    }

    public function test_firma_en_tableta_bloquea_el_inventario_y_genera_pdf(): void
    {
        Queue::fake();

        Livewire::test(EditCheckin::class, ['record' => $this->checkin->getRouteKey()])
            ->fillForm([
                ...$this->llenarInventario(),
                'firma_tableta' => $this->firmaPng(),
                'firma_nombre' => 'Juan Pérez',
                'firma_acepta_condiciones' => true,
                'firma_acepta_aviso' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $c = $this->checkin->fresh();
        $this->assertTrue($c->estaFirmado());
        $this->assertSame('Juan Pérez', $c->firmado_por);
        $this->assertSame('tableta', $c->firma_medio);
        $this->assertTrue($c->huellaVigente());
        Media::disco()->assertExists($c->firma);
        Queue::assertPushed(GenerarPdfIngreso::class, fn ($job) => $job->checkinId === $c->id && $job->enviarAlCliente);

        Livewire::test(EditCheckin::class, ['record' => $c->getRouteKey()])
            ->assertFormFieldDisabled('kilometraje');
    }

    public function test_sin_aceptar_condiciones_no_se_firma(): void
    {
        $this->expectException(ValidationException::class);

        app(FirmarCheckin::class)($this->checkin, $this->firmaPng(), 'Juan', true, false, 'tableta');
    }

    public function test_una_firma_vacia_se_rechaza(): void
    {
        $vacia = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($vacia);
        $png = 'data:image/png;base64,'.base64_encode(ob_get_clean());

        $this->expectException(ValidationException::class);

        app(FirmarCheckin::class)($this->checkin, $png, 'Juan', true, true, 'tableta');
    }

    public function test_si_el_inventario_cambia_despues_de_firmar_la_huella_lo_detecta(): void
    {
        Queue::fake();
        $this->checkin->update($this->llenarInventario());
        app(FirmarCheckin::class)($this->checkin->fresh(), $this->firmaPng(), 'Juan', true, true, 'tableta');

        $c = $this->checkin->fresh();
        $this->assertTrue($c->huellaVigente());

        $c->update(['pertenencias' => 'Laptop']);
        $this->assertFalse($c->fresh()->huellaVigente());
    }

    public function test_el_cliente_firma_desde_el_enlace(): void
    {
        Queue::fake();
        $this->checkin->update($this->llenarInventario());
        auth()->logout();

        $enlace = EnlaceFirma::generar($this->checkin);

        $this->get($enlace)->assertOk()->assertSee($this->orden->folio)->assertSee('Lentes en la guantera');

        $this->post($enlace, [
            'nombre' => 'Juan Pérez',
            'firma' => $this->firmaPng(),
            'acepta_aviso_privacidad' => '1',
            'acepta_condiciones' => '1',
        ])->assertRedirect(route('firma.gracias', ['folio' => $this->orden->folio]));

        $c = $this->checkin->fresh();
        $this->assertSame('enlace', $c->firma_medio);
        $this->assertNotNull($c->firma_ip);

        // Volver a abrir el enlace muestra que ya está firmado, sin formulario.
        $this->get($enlace)->assertOk()->assertSee('ya fue firmado')->assertDontSee('Firmar inventario');
    }

    public function test_el_enlace_sin_firma_o_vencido_no_funciona(): void
    {
        auth()->logout();

        $this->get(route('firma.mostrar', $this->checkin))->assertForbidden();

        $vencido = URL::temporarySignedRoute('firma.mostrar', now()->subMinute(), ['checkin' => $this->checkin->id], absolute: false);
        $this->get($vencido)->assertForbidden();

        // Cambiar el número de check-in en un enlace válido no da acceso a otro inventario.
        $otro = Checkin::create(['orden_servicio_id' => (new AbrirOrden)(Vehiculo::factory()->create(), TipoOrden::Particular)->id]);
        $alterado = str_replace("/firma/{$this->checkin->id}?", "/firma/{$otro->id}?", EnlaceFirma::generar($this->checkin));
        $this->get($alterado)->assertForbidden();
    }

    public function test_el_enlace_funciona_con_otro_dominio_o_esquema(): void
    {
        auth()->logout();
        $enlace = EnlaceFirma::generar($this->checkin);
        $otroHost = preg_replace('#^https?://[^/]+#', 'https://taller.ejemplo.mx', $enlace);

        $this->get($otroHost)->assertOk()->assertSee($this->orden->folio);
    }

    public function test_un_enlace_vencido_muestra_un_mensaje_en_espanol(): void
    {
        auth()->logout();
        config(['app.debug' => false]);

        $this->get(route('firma.mostrar', $this->checkin))
            ->assertForbidden()
            ->assertSee('Este enlace no es válido o ya venció');
    }

    public function test_el_enlace_de_whatsapp_lleva_el_mensaje_listo(): void
    {
        $url = EnlaceFirma::whatsapp($this->checkin);

        $this->assertStringStartsWith('https://wa.me/527221234567?text=', $url);
        $this->assertStringContainsString(rawurlencode($this->orden->folio), $url);
    }

    public function test_el_pdf_se_genera_y_se_envia_al_cliente(): void
    {
        Mail::fake();
        Queue::fake();
        $this->checkin->update($this->llenarInventario());
        app(FirmarCheckin::class)($this->checkin->fresh(), $this->firmaPng(), 'Juan Pérez', true, true, 'tableta');

        (new GenerarPdfIngreso($this->checkin->id, enviarAlCliente: true))->handle();

        $c = $this->checkin->fresh();
        Media::disco()->assertExists($c->pdf);
        $this->assertStringStartsWith('%PDF', Media::disco()->get($c->pdf));
        Mail::assertSent(ComprobanteIngreso::class, fn ($m) => $m->hasTo('cliente@example.com'));
    }

    public function test_descarga_del_pdf_requiere_permiso(): void
    {
        $this->get(route('ordenes.pdf-ingreso', $this->orden))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->usuario(Rol::Tecnico));
        $this->get(route('ordenes.pdf-ingreso', $this->orden))->assertForbidden();

        auth()->logout();
        $this->get(route('ordenes.pdf-ingreso', $this->orden))->assertRedirect(route('filament.taller.auth.login'));
    }

    public function test_las_fotos_privadas_solo_se_ven_con_url_firmada(): void
    {
        // Usar la configuración real del disco (Storage::fake la reemplaza y le quita 'serve').
        config(['filesystems.disks.media' => (require config_path('filesystems.php'))['disks']['media']]);
        Storage::forgetDisk('media');
        $ruta = 'pruebas/'.uniqid().'.jpg';
        Media::disco()->put($ruta, 'contenido');

        try {
            $temporal = Media::urlTemporal($ruta, 5);
            $this->assertStringContainsString('/archivos/'.$ruta, $temporal);

            auth()->logout();
            $this->get($temporal)->assertOk();
            $this->get('/archivos/'.$ruta)->assertForbidden();
        } finally {
            Media::disco()->delete($ruta);
        }
    }
}
