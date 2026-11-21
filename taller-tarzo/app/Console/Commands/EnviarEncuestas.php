<?php

namespace App\Console\Commands;

use App\Actions\Postventa\EnviarEncuestas as Accion;
use Illuminate\Console\Command;

class EnviarEncuestas extends Command
{
    protected $signature = 'tarzo:enviar-encuestas';

    protected $description = 'Envía la encuesta NPS 24 h después de la entrega.';

    public function handle(): int
    {
        $n = app(Accion::class)();

        $this->info("Encuestas enviadas: {$n}");

        return self::SUCCESS;
    }
}
