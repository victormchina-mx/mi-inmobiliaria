<?php

namespace App\Services\Comunicacion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente mínimo de la WhatsApp Business Platform (Cloud API de Meta).
 *  - Mensajes iniciados por el taller: solo con plantillas aprobadas por Meta.
 *  - Texto libre: solo dentro de las 24 h siguientes al último mensaje del cliente.
 */
class WhatsAppCloud
{
    public function activo(): bool
    {
        return in_array(config('tarzo.whatsapp.driver'), ['cloud', 'log'], true);
    }

    /**
     * @param  list<string>  $parametros  valores de {{1}}, {{2}}… del cuerpo de la plantilla
     * @return string id del mensaje en WhatsApp (wamid)
     */
    public function enviarPlantilla(string $telefono, string $plantilla, string $idioma, array $parametros): string
    {
        return $this->enviar($telefono, [
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => ['code' => $idioma],
                'components' => $parametros === [] ? [] : [[
                    'type' => 'body',
                    'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], array_values($parametros)),
                ]],
            ],
        ]);
    }

    public function enviarTexto(string $telefono, string $texto): string
    {
        return $this->enviar($telefono, ['type' => 'text', 'text' => ['body' => $texto, 'preview_url' => true]]);
    }

    private function enviar(string $telefono, array $contenido): string
    {
        $payload = ['messaging_product' => 'whatsapp', 'to' => $telefono, ...$contenido];

        if (config('tarzo.whatsapp.driver') === 'log') {
            Log::info('WhatsApp simulado', $payload);

            return 'log-'.bin2hex(random_bytes(6));
        }

        $c = config('tarzo.whatsapp');
        if (blank($c['token']) || blank($c['phone_number_id'])) {
            throw new RuntimeException('WhatsApp no está configurado: faltan WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID.');
        }

        $respuesta = Http::withToken($c['token'])->timeout(15)
            ->post("https://graph.facebook.com/{$c['version']}/{$c['phone_number_id']}/messages", $payload);

        if ($respuesta->failed()) {
            throw new RuntimeException('WhatsApp respondió '.$respuesta->status().': '.($respuesta->json('error.message') ?? $respuesta->body()));
        }

        return (string) $respuesta->json('messages.0.id');
    }

    /**
     * Valida la firma X-Hub-Signature-256 que Meta pone en cada webhook.
     */
    public static function firmaValida(string $cuerpo, ?string $firma): bool
    {
        $secreto = config('tarzo.whatsapp.app_secret');

        return filled($secreto) && filled($firma)
            && hash_equals('sha256='.hash_hmac('sha256', $cuerpo, $secreto), $firma);
    }
}
