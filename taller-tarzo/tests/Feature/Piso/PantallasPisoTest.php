<?php

namespace Tests\Feature\Piso;

use App\Actions\Ordenes\CambiarEtapa;
use App\Enums\EstadoTarea;
use App\Enums\Rol;
use App\Filament\Pages\CargaTrabajo;
use App\Filament\Pages\MisTareas;
use App\Filament\Pages\Tablero;
use App\Filament\Resources\OrdenServicios\Pages\ViewOrdenServicio;
use App\Filament\Resources\OrdenServicios\RelationManagers\EvidenciasRelationManager;
use App\Filament\Resources\OrdenServicios\RelationManagers\TareasRelationManager;
use App\Jobs\MarcarEvidencia;
use App\Models\Etapa;
use App\Models\Evidencia;
use App\Support\Checklists;
use App\Support\Media;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class PantallasPisoTest extends PisoTestCase
{
    public function test_el_tablero_muestra_las_ordenes_por_etapa_y_filtra(): void
    {
        $orden = $this->ordenAprobada();
        $otra = $this->orden();
        $this->actingAs($this->jefe);

        Livewire::test(Tablero::class)
            ->assertOk()
            ->assertSee($orden->folio)
            ->assertSee($otra->folio)
            ->set('buscar', $orden->folio)
            ->assertSee($orden->folio)
            ->assertDontSee($otra->folio);
    }

    public function test_mover_en_el_tablero_aplica_las_reglas(): void
    {
        $orden = $this->orden();
        $this->actingAs($this->jefe);

        Livewire::test(Tablero::class)->call('moverOrden', $orden->id, $this->etapa('mecanica')->id)->assertNotified();
        $this->assertSame(Etapa::RECEPCION, $orden->fresh()->etapa->clave, 'Sin cotización no se mueve');

        Livewire::test(Tablero::class)->call('moverOrden', $orden->id, $this->etapa('diagnostico')->id);
        $this->assertSame('diagnostico', $orden->fresh()->etapa->clave);

        Livewire::test(Tablero::class)
            ->callAction('mover', data: ['etapa_id' => $this->etapa('mecanica')->id, 'forzar' => true, 'comentario' => 'Garantía verbal'], arguments: ['orden' => $orden->id])
            ->assertHasNoActionErrors();
        $this->assertSame('mecanica', $orden->fresh()->etapa->clave);
    }

    public function test_el_asesor_ve_el_tablero_pero_no_mueve(): void
    {
        $orden = $this->orden();

        Livewire::test(Tablero::class)->assertOk()->assertDontSee('Mover…')
            ->call('moverOrden', $orden->id, $this->etapa('diagnostico')->id);
        $this->assertSame(Etapa::RECEPCION, $orden->fresh()->etapa->clave);
    }

    public function test_modo_tv(): void
    {
        $orden = $this->orden();

        $this->get(route('tablero.tv'))->assertOk()->assertSee($orden->folio)->assertSee('http-equiv="refresh"', false);

        auth()->logout();
        $this->get(route('tablero.tv'))->assertRedirect();
    }

    public function test_el_tecnico_trabaja_desde_mis_tareas(): void
    {
        $tecnico = $this->tecnicoConUsuario();
        $orden = $this->ordenAprobada();
        $tarea = $orden->tareas->first();
        $this->actingAs($tecnico->user);

        // La tarea sin asignar de su área aparece como disponible y la puede tomar.
        Livewire::test(MisTareas::class)->assertSee('Disponibles en tu área')->assertSee($tarea->descripcion)
            ->call('iniciar', $tarea->id);
        $this->assertSame($tecnico->id, $tarea->fresh()->tecnico_id);
        $this->assertSame(EstadoTarea::EnProceso, $tarea->fresh()->estado);

        Livewire::test(MisTareas::class)
            ->callAction('pausar', data: ['motivo' => 'espera_refaccion'], arguments: ['tarea' => $tarea->id])
            ->assertHasNoActionErrors();
        $this->assertSame(EstadoTarea::Pausada, $tarea->fresh()->estado);

        Livewire::test(MisTareas::class)
            ->callAction('evidencia', data: ['momento' => 'desmontada', 'archivos' => [UploadedFile::fake()->image('balata.jpg', 800, 600)]], arguments: ['tarea' => $tarea->id])
            ->assertHasNoActionErrors();
        $this->assertSame(1, $orden->evidencias()->count());

        Livewire::test(MisTareas::class)->call('iniciar', $tarea->id)->call('terminar', $tarea->id);
        $this->assertSame(EstadoTarea::Terminada, $tarea->fresh()->estado);

        // Navegación del técnico: no entra a carga de trabajo ni a clientes.
        $this->get('/panel/carga-trabajo')->assertForbidden();
        $this->get('/panel/tablero')->assertOk();
    }

    public function test_un_usuario_sin_ficha_de_tecnico_no_ve_mis_tareas(): void
    {
        $this->actingAs($this->usuario(Rol::Tecnico));
        $this->get('/panel/mis-tareas')->assertForbidden();
    }

    public function test_la_marca_de_agua_se_aplica_a_la_foto(): void
    {
        $orden = $this->orden();
        $img = imagecreatetruecolor(800, 600);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 200, 200));
        ob_start();
        imagejpeg($img);
        Media::disco()->put('evidencias/p.jpg', ob_get_clean());
        $e = Evidencia::create(['orden_servicio_id' => $orden->id, 'etapa_id' => $orden->etapa_id, 'momento' => 'terminado', 'archivo' => 'evidencias/p.jpg']);

        (new MarcarEvidencia($e->id))->handle();

        $marcada = imagecreatefromstring(Media::disco()->get('evidencias/p.jpg'));
        $this->assertNotNull($e->fresh()->marcada_at);
        $this->assertLessThan(120, imagecolorat($marcada, 400, 595) & 0xFF, 'La franja inferior es oscura');
        $this->assertSame(800, imagesx($marcada));
    }

    public function test_asignar_tareas_y_carga_de_trabajo(): void
    {
        $tecnico = $this->tecnicoConUsuario();
        $orden = $this->ordenAprobada();
        $tarea = $orden->tareas->first();
        $this->actingAs($this->jefe);

        Livewire::test(TareasRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => ViewOrdenServicio::class])
            ->callAction(TestAction::make('edit')->table($tarea), data: ['tecnico_id' => $tecnico->id, 'horas_estandar' => 2, 'descripcion' => $tarea->descripcion])
            ->assertHasNoActionErrors();
        $this->assertSame($tecnico->id, $tarea->fresh()->tecnico_id);

        Livewire::test(CargaTrabajo::class)->assertOk()->assertSee('Diego Sánchez')->assertSee('25 %');
    }

    public function test_calidad_y_entrega_desde_la_orden(): void
    {
        $orden = $this->ordenAprobada();
        (new CambiarEtapa)($orden, $this->etapa('calidad'), $this->jefe);
        $this->actingAs($this->usuario(Rol::Dueno));

        Livewire::test(ViewOrdenServicio::class, ['record' => $orden->getRouteKey()])
            ->callAction('calidad', data: ['cumplidos' => array_keys(Checklists::CALIDAD)])
            ->assertHasNoActionErrors();
        $this->assertSame(Etapa::LISTO_ENTREGA, $orden->fresh()->etapa->clave);

        Livewire::test(ViewOrdenServicio::class, ['record' => $orden->getRouteKey()])
            ->callAction('entregar', data: ['cumplidos' => array_keys(Checklists::ENTREGA), 'nombre' => 'Laura Hernández', 'firma' => $this->firmaPng()])
            ->assertHasNoActionErrors();
        $this->assertSame(Etapa::ENTREGADO, $orden->fresh()->etapa->clave);
    }

    public function test_evidencias_en_la_orden_y_album(): void
    {
        $orden = $this->orden();

        Livewire::test(EvidenciasRelationManager::class, ['ownerRecord' => $orden, 'pageClass' => ViewOrdenServicio::class])
            ->callAction(TestAction::make('subir')->table(), data: [
                'momento' => 'danada', 'archivos' => [UploadedFile::fake()->image('a.jpg')], 'visible_cliente' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue($orden->evidencias()->first()->visible_cliente);
        $this->get(route('ordenes.album', [$orden, 'cliente' => 1]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
