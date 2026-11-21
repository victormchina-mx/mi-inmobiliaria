<?php

namespace App\Console\Commands;

use App\Actions\Postventa\RevisarGarantias as Accion;
use Illuminate\Console\Command;

class RevisarGarantias extends Command
{
    protected $signature = 'tarzo:revisar-garantias';

    protected $description = 'Vence garantías y avisa de las próximas a vencer.';

    public function handle(): int
    {
        $n = app(Accion::class)();

        $this->info("Garantías por vencer avisadas: {$n}");

        return self::SUCCESS;
    }
}
