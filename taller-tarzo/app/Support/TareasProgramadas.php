<?php

namespace App\Support;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Tareas programadas del sistema. Un solo cron cada minuto ejecuta
 * `php artisan schedule:run` (ver deploy/README.md).
 */
final class TareasProgramadas
{
    public static function registrar(Schedule $schedule): void
    {
        // Hosting compartido: sin Supervisor, el cron procesa la cola cada minuto.
        // En VPS se pone TARZO_COLA_POR_CRON=false y Horizon/Supervisor toma el relevo.
        if (config('tarzo.cola.por_cron')) {
            $schedule->command('queue:work --stop-when-empty', [
                '--queue' => config('tarzo.cola.colas'),
                '--max-time' => config('tarzo.cola.max_segundos'),
                '--tries' => 3,
                '--backoff' => 30,
            ])
                ->name('procesar-cola')
                ->everyMinute()
                ->withoutOverlapping(5);
        }

        $schedule->command('tarzo:recordar-cotizaciones')->hourly()->withoutOverlapping();
        $schedule->command('tarzo:revisar-compras')->dailyAt('08:05');

        // Módulo 5 · postventa
        $schedule->command('tarzo:generar-recordatorios')->monthlyOn(1, '06:15');
        $schedule->command('tarzo:enviar-recordatorios')->dailyAt('10:20')->withoutOverlapping();
        $schedule->command('tarzo:enviar-encuestas')->hourlyAt(25)->between('9:00', '20:00')->withoutOverlapping();
        $schedule->command('tarzo:revisar-garantias')->dailyAt('07:40');

        // Módulo 6 · reportes
        $schedule->command('tarzo:resumen-mensual')->monthlyOn(1, '07:10');
        $schedule->command('queue:prune-failed', ['--hours' => 24 * 30])->daily()->at('03:10');
        $schedule->command('activitylog:clean')->monthly();
        $schedule->command('auth:clear-resets')->daily()->at('03:20');
    }
}
