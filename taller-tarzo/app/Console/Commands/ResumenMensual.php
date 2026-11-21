<?php

namespace App\Console\Commands;

use App\Mail\ResumenMensual as Correo;
use App\Support\Avisos;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * El día 1 envía a la gerencia los KPIs del mes anterior con el Excel adjunto.
 */
class ResumenMensual extends Command
{
    protected $signature = 'tarzo:resumen-mensual {--mes= : AAAA-MM, por defecto el mes anterior}';

    protected $description = 'Envía por correo el resumen mensual de indicadores.';

    public function handle(): int
    {
        $inicio = $this->option('mes') ? Carbon::createFromFormat('Y-m-d', $this->option('mes').'-01')->startOfMonth() : now()->subMonthNoOverflow()->startOfMonth();
        $destinos = Avisos::usuariosCon('reportes.gestionar')->pluck('email')->filter()->unique();

        foreach ($destinos as $email) {
            Mail::to($email)->queue(new Correo($inicio->toDateString()));
        }

        $this->info("Resumen de {$inicio->format('Y-m')} enviado a {$destinos->count()} destinatarios.");

        return self::SUCCESS;
    }
}
