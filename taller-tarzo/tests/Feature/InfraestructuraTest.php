<?php

namespace Tests\Feature;

use App\Support\TareasProgramadas;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class InfraestructuraTest extends TestCase
{
    private function tareasProgramadas(): array
    {
        $schedule = new Schedule;
        TareasProgramadas::registrar($schedule);

        return collect($schedule->events())->map(fn ($e) => $e->command)->all();
    }

    public function test_en_hosting_compartido_el_cron_procesa_la_cola(): void
    {
        config(['tarzo.cola.por_cron' => true]);

        $cola = collect($this->tareasProgramadas())->first(fn ($c) => str_contains($c, 'queue:work'));

        $this->assertNotNull($cola, 'Falta la tarea queue:work en el scheduler');
        $this->assertStringContainsString('--stop-when-empty', $cola);
        $this->assertStringContainsString('--max-time=50', $cola);
        $this->assertStringNotContainsString('--stop-when-empty=', $cola, 'La opción no acepta valor; con valor el worker no arranca');
    }

    public function test_en_vps_el_cron_no_procesa_la_cola(): void
    {
        config(['tarzo.cola.por_cron' => false]);

        $this->assertFalse(collect($this->tareasProgramadas())->contains(fn ($c) => str_contains($c, 'queue:work')));
    }

    public function test_el_scheduler_real_incluye_las_tareas(): void
    {
        $comandos = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode(' ');

        $this->assertStringContainsString('queue:prune-failed', $comandos);
    }

    public function test_la_zona_horaria_e_idioma_son_de_mexico(): void
    {
        $this->assertSame('America/Mexico_City', config('app.timezone'));
        $this->assertSame('es', app()->getLocale());
        $this->assertSame('Debes aceptar términos.', __('validation.accepted', ['attribute' => 'términos']));
    }
}
