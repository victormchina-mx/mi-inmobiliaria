<?php

namespace App\Actions\Postventa;

use App\Enums\EstadoMensaje;
use App\Enums\EventoMensaje;
use App\Models\Recordatorio;
use App\Services\Comunicacion\Notificador;

/**
 * Envía los recordatorios con fecha de hoy o anterior. Si el auto está en el taller, se pospone.
 */
class EnviarRecordatorios
{
    public function __invoke(): int
    {
        $enviados = 0;

        Recordatorio::with(['cliente', 'vehiculo'])->where('estado', 'programado')->whereDate('fecha', '<=', today())->get()
            ->each(function (Recordatorio $r) use (&$enviados) {
                if ($r->vehiculo->ordenes()->whereNull('cerrada_at')->exists()) {
                    return; // ya está en el taller
                }

                $this->enviar($r) && $enviados++;
            });

        return $enviados;
    }

    /**
     * @return bool si salió por algún canal (false si se omitió: sin consentimiento, sin datos de contacto…)
     */
    public function enviar(Recordatorio $r): bool
    {
        $mensaje = app(Notificador::class)->avisar(
            $r->tipo === 'verificacion' ? EventoMensaje::RecordatorioVerificacion : EventoMensaje::RecordatorioServicio,
            $r->cliente,
            null,
            [
                'vehiculo' => trim($r->vehiculo->marca.' '.$r->vehiculo->modelo),
                'placas' => $r->vehiculo->placas,
                'servicio' => $r->titulo,
                'km' => $r->km_estimado ? number_format($r->km_estimado) : null,
            ],
        );

        $salio = $mensaje->estado !== EstadoMensaje::Omitido;
        $r->update(['estado' => $salio ? 'enviado' : 'cancelado', 'mensaje_id' => $mensaje->id]);

        return $salio;
    }
}
