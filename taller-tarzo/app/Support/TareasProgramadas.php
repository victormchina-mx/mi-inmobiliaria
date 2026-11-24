<?php

namespace App\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/**
 * Tareas programadas del sistema. Un solo cron cada minuto ejecuta
 * `php artisan schedule:run` (ver deploy/README.md).
 *
 * Algunos planes compartidos (Hostinger) desactivan `proc_open`, que el scheduler usa para lanzar
 * cada comando en otro proceso. En ese caso las tareas corren dentro del mismo proceso con
 * Artisan::call (mismo horario y mismo bloqueo contra ejecuciones simultáneas).
 */
final class TareasProgramadas
{
    public static function registrar(Schedule $schedule): void
    {
        // Hosting compartido: sin Supervisor, el cron procesa la cola cada minuto.
        // En VPS se pone TARZO_COLA_POR_CRON=false y Horizon/Supervisor toma el relevo.
        if (config('tarzo.cola.por_cron')) {
            self::comando($schedule, 'queue:work', [
                '--stop-when-empty' => true,
                '--queue' => config('tarzo.cola.colas'),
                '--max-time' => config('tarzo.cola.max_segundos'),
                '--tries' => 3,
                '--backoff' => 30,
            ], 'procesar-cola')->everyMinute()->withoutOverlapping(5);
        }

        self::comando($schedule, 'tarzo:recordar-cotizaciones')->hourly()->withoutOverlapping();
        self::comando($schedule, 'tarzo:revisar-compras')->dailyAt('08:05');

        // Módulo 5 · postventa
        self::comando($schedule, 'tarzo:generar-recordatorios')->monthlyOn(1, '06:15');
        self::comando($schedule, 'tarzo:enviar-recordatorios')->dailyAt('10:20')->withoutOverlapping();
        self::comando($schedule, 'tarzo:enviar-encuestas')->hourlyAt(25)->between('9:00', '20:00')->withoutOverlapping();
        self::comando($schedule, 'tarzo:revisar-garantias')->dailyAt('07:40');

        // Módulo 6 · reportes
        self::comando($schedule, 'tarzo:resumen-mensual')->monthlyOn(1, '07:10');

        self::comando($schedule, 'queue:prune-failed', ['--hours' => 24 * 30])->daily()->at('03:10');
        self::comando($schedule, 'activitylog:clean')->monthly();
        self::comando($schedule, 'auth:clear-resets')->daily()->at('03:20');
    }

    /**
     * ¿Se pueden lanzar procesos hijos? (TARZO_CRON_EN_PROCESO=true fuerza el modo sin proc_open)
     */
    public static function puedeLanzarProcesos(): bool
    {
        return ! config('tarzo.cola.cron_en_proceso') && function_exists('proc_open');
    }

    /**
     * @param  array<string, mixed>  $parametros
     */
    private static function comando(Schedule $schedule, string $comando, array $parametros = [], ?string $nombre = null): Event
    {
        if (self::puedeLanzarProcesos()) {
            $evento = $schedule->command($comando, $parametros);

            return $nombre ? $evento->name($nombre) : $evento;
        }

        $descripcion = trim($comando.' '.collect($parametros)
            ->map(fn ($v, $k) => $v === true ? $k : "{$k}=".(is_array($v) ? implode(',', $v) : $v))->implode(' '));

        return $schedule->call(fn () => Artisan::call($comando, $parametros))->name($nombre ?? $descripcion)->description($descripcion);
    }
}
