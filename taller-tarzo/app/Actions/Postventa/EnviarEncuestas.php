<?php

namespace App\Actions\Postventa;

use App\Enums\EventoMensaje;
use App\Models\EncuestaNps;
use App\Models\OrdenServicio;
use App\Services\Comunicacion\Notificador;
use Illuminate\Support\Facades\URL;

/**
 * Envía la encuesta NPS 24 horas después de la entrega (órdenes de hasta 7 días).
 */
class EnviarEncuestas
{
    public function __invoke(): int
    {
        $n = 0;

        OrdenServicio::with(['cliente', 'vehiculo'])
            ->whereNotNull('fecha_entrega')
            ->whereBetween('fecha_entrega', [now()->subDays(7), now()->subHours(24)])
            ->where('tipo', '!=', 'interna')
            ->whereDoesntHave('encuesta')
            ->get()
            ->each(function (OrdenServicio $orden) use (&$n) {
                $encuesta = EncuestaNps::create(['orden_servicio_id' => $orden->id, 'cliente_id' => $orden->cliente_id, 'enviada_at' => now()]);

                $mensaje = app(Notificador::class)->avisar(EventoMensaje::EncuestaNps, $orden->cliente, $orden, [
                    'enlace_encuesta' => self::enlace($encuesta),
                ]);

                $encuesta->update(['mensaje_id' => $mensaje->id]);
                $n++;
            });

        return $n;
    }

    public static function enlace(EncuestaNps $encuesta): string
    {
        return url(URL::temporarySignedRoute('encuesta.mostrar', now()->addDays(30), ['encuesta' => $encuesta->id], absolute: false));
    }
}
