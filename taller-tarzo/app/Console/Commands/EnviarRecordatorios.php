<?php

namespace App\Console\Commands;

use App\Actions\Postventa\EnviarRecordatorios as Accion;
use Illuminate\Console\Command;

class EnviarRecordatorios extends Command
{
    protected $signature = 'tarzo:enviar-recordatorios';

    protected $description = 'Envía los recordatorios que vencen hoy.';

    public function handle(): int
    {
        $n = app(Accion::class)();

        $this->info("Recordatorios enviados: {$n}");

        return self::SUCCESS;
    }
}
