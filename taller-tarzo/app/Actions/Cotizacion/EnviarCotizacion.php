<?php

namespace App\Actions\Cotizacion;

use App\Enums\EstadoCotizacion;
use App\Enums\EventoMensaje;
use App\Mail\PresupuestoParaAutorizar;
use App\Models\Cotizacion;
use App\Services\Comunicacion\Notificador;
use App\Services\Cotizacion\Cotizador;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

/**
 * Marca la cotización como enviada y, si se pide, manda el enlace por correo.
 */
class EnviarCotizacion
{
    /**
     * @param  list<string>  $correoA  roles a los que se envía correo (cliente, ajustador) y/o 'whatsapp_api'
     *                                 (aviso automático al cliente por WhatsApp/correo según su preferencia)
     */
    public function __invoke(Cotizacion $cotizacion, array $correoA = []): Cotizacion
    {
        if (! in_array($cotizacion->estado, [EstadoCotizacion::Borrador, EstadoCotizacion::Enviada], true)) {
            throw ValidationException::withMessages(['cotizacion' => 'Esta cotización ya fue respondida. Crea un suplemento para cambios.']);
        }

        $cotizacion = app(Cotizador::class)->recalcular($cotizacion);

        if ($cotizacion->partidasPendientes()->doesntExist()) {
            throw ValidationException::withMessages(['cotizacion' => 'No hay partidas pendientes de autorizar.']);
        }

        $cotizacion->forceFill([
            'estado' => EstadoCotizacion::Enviada,
            'enviada_at' => $cotizacion->enviada_at ?? now(),
            'recordatorio_at' => null,
        ])->save();

        if (in_array('whatsapp_api', $correoA, true)) {
            app(Notificador::class)->avisar(EventoMensaje::PresupuestoListo, $cotizacion->orden->cliente, $cotizacion->orden, [
                'total' => Number::currency($cotizacion->total),
                'enlace_aprobacion' => EnlaceAprobacion::generar($cotizacion, 'cliente'),
            ]);
        }

        foreach (array_diff($correoA, ['whatsapp_api']) as $rol) {
            $correo = $rol === 'ajustador' ? $cotizacion->orden->siniestro?->ajustador_email : $cotizacion->orden->cliente->email;
            if (filled($correo)) {
                Mail::to($correo)->queue(new PresupuestoParaAutorizar($cotizacion, $rol));
            }
        }

        return $cotizacion;
    }
}
