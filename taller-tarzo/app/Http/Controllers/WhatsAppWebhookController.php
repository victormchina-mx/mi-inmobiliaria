<?php

namespace App\Http\Controllers;

use App\Enums\EstadoMensaje;
use App\Filament\Pages\Conversaciones;
use App\Models\Cliente;
use App\Models\Mensaje;
use App\Services\Comunicacion\WhatsAppCloud;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Webhook de la WhatsApp Cloud API: confirma la suscripción, actualiza estados de entrega
 * y guarda los mensajes que escriben los clientes.
 */
class WhatsAppWebhookController extends Controller
{
    public function verificar(Request $request): Response
    {
        $ok = $request->query('hub_mode') === 'subscribe'
            && filled(config('tarzo.whatsapp.verify_token'))
            && hash_equals((string) config('tarzo.whatsapp.verify_token'), (string) $request->query('hub_verify_token'));

        return $ok ? response((string) $request->query('hub_challenge'), 200) : response('Token inválido', 403);
    }

    public function recibir(Request $request): Response
    {
        if (! WhatsAppCloud::firmaValida($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response('Firma inválida', 403);
        }

        foreach ($request->input('entry', []) as $entrada) {
            foreach ($entrada['changes'] ?? [] as $cambio) {
                $valor = $cambio['value'] ?? [];

                foreach ($valor['statuses'] ?? [] as $estado) {
                    $this->actualizarEstado($estado);
                }

                foreach ($valor['messages'] ?? [] as $mensaje) {
                    $this->guardarEntrante($mensaje, $valor['contacts'][0]['profile']['name'] ?? null);
                }
            }
        }

        return response('ok', 200);
    }

    private function actualizarEstado(array $s): void
    {
        $mensaje = Mensaje::where('proveedor_id', $s['id'] ?? '')->first();
        if (! $mensaje) {
            return;
        }

        $cuando = isset($s['timestamp']) ? now()->setTimestamp((int) $s['timestamp']) : now();

        match ($s['status'] ?? null) {
            'delivered' => $mensaje->estado !== EstadoMensaje::Leido && $mensaje->update(['estado' => EstadoMensaje::Entregado, 'entregado_at' => $cuando]),
            'read' => $mensaje->update(['estado' => EstadoMensaje::Leido, 'leido_at' => $cuando, 'entregado_at' => $mensaje->entregado_at ?? $cuando]),
            'failed' => $mensaje->update(['estado' => EstadoMensaje::Fallido, 'error' => $s['errors'][0]['title'] ?? $s['errors'][0]['message'] ?? 'WhatsApp no pudo entregar el mensaje']),
            default => null,
        };
    }

    private function guardarEntrante(array $m, ?string $nombre): void
    {
        if (Mensaje::where('proveedor_id', $m['id'] ?? '')->exists()) {
            return; // Meta puede repetir el aviso
        }

        $cliente = Cliente::porTelefono((string) ($m['from'] ?? ''));
        $texto = match ($m['type'] ?? 'text') {
            'text' => $m['text']['body'] ?? '',
            'button' => $m['button']['text'] ?? '',
            'interactive' => $m['interactive']['button_reply']['title'] ?? $m['interactive']['list_reply']['title'] ?? '',
            default => '['.($m['type'] ?? 'mensaje').' recibido: revísalo en WhatsApp]',
        };

        $orden = $cliente?->ordenes()->whereNull('cerrada_at')->latest('id')->first();

        Mensaje::create([
            'direccion' => 'entrante',
            'cliente_id' => $cliente?->id,
            'orden_servicio_id' => $orden?->id,
            'canal' => 'whatsapp',
            'destino' => $m['from'] ?? null,
            'cuerpo' => $texto,
            'estado' => EstadoMensaje::Recibido,
            'proveedor_id' => $m['id'] ?? null,
        ]);

        $quien = $cliente?->nombre_completo ?? $nombre ?? ('+'.($m['from'] ?? ''));
        $aviso = Notification::make()->title("WhatsApp de {$quien}")->body(Str::limit($texto, 120))
            ->icon('heroicon-o-chat-bubble-left-right')->info()
            ->actions([Action::make('ver')->label('Responder')->url(Conversaciones::getUrl(['cliente' => $cliente?->id], panel: 'taller'))]);

        $orden?->asesor ? $aviso->sendToDatabase($orden->asesor) : Avisos::a('crm.gestionar', $aviso);
    }
}
