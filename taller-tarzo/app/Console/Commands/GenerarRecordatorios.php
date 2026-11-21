<?php

namespace App\Console\Commands;

use App\Actions\Postventa\GenerarRecordatorios as Accion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerarRecordatorios extends Command
{
    protected $signature = 'tarzo:generar-recordatorios {--mes= : AAAA-MM, por defecto el mes actual}';

    protected $description = 'Programa los recordatorios de servicio y verificación del mes.';

    public function handle(): int
    {
        $n = app(Accion::class)($this->option('mes') ? Carbon::parse($this->option('mes').'-01') : null);

        $this->info("Recordatorios programados: {$n}");

        return self::SUCCESS;
    }
}
