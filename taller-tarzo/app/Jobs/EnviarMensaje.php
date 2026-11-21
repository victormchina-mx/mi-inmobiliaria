<?php

namespace App\Jobs;

use App\Enums\EstadoMensaje;
use App\Mail\MensajeCorreo;
use App\Models\Mensaje;
use App\Services\Comunicacion\WhatsAppCloud;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envía un mensaje registrado. Reintenta 3 veces; si falla queda visible con el error en "Mensajes".
 */
class EnviarMensaje implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $mensajeId)
    {
        $this->onQueue('alta');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(WhatsAppCloud $whatsapp): void
    {
        $mensaje = Mensaje::with('cliente')->find($this->mensajeId);

        if (! $mensaje || ! in_array($mensaje->estado, [EstadoMensaje::Pendiente, EstadoMensaje::Fallido], true)) {
            return;
        }

        $mensaje->increment('intentos');

        if ($mensaje->canal === 'correo') {
            Mail::to($mensaje->destino)->send(new MensajeCorreo($mensaje));
            $proveedorId = null;
        } else {
            $meta = $mensaje->meta;
            $proveedorId = filled($meta['plantilla'] ?? null)
                ? $whatsapp->enviarPlantilla($mensaje->destino, $meta['plantilla'], $meta['idioma'] ?? config('tarzo.whatsapp.idioma'), $meta['parametros'] ?? [])
                : $whatsapp->enviarTexto($mensaje->destino, $mensaje->cuerpo);
        }

        $mensaje->forceFill(['estado' => EstadoMensaje::Enviado, 'proveedor_id' => $proveedorId, 'enviado_at' => now(), 'error' => null])->save();
    }

    public function failed(Throwable $e): void
    {
        Mensaje::whereKey($this->mensajeId)->update(['estado' => EstadoMensaje::Fallido->value, 'error' => mb_substr($e->getMessage(), 0, 1000)]);
    }
}
