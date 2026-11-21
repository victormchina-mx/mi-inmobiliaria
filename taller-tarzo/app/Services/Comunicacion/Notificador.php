<?php

namespace App\Services\Comunicacion;

use App\Enums\CanalPreferido;
use App\Enums\EstadoMensaje;
use App\Enums\EventoMensaje;
use App\Jobs\EnviarMensaje;
use App\Models\Cliente;
use App\Models\Mensaje;
use App\Models\OrdenServicio;
use App\Models\PlantillaMensaje;
use App\Models\User;
use App\Support\EnlacePortal;

/**
 * Decide por qué canal avisar al cliente, arma el mensaje con su plantilla y lo deja en cola.
 *
 *  - Canal según la preferencia del cliente: WhatsApp (si está activo y tiene número) o correo;
 *    si el canal preferido no está disponible se usa el otro. "Llamada" no recibe mensajes automáticos.
 *  - Recordatorios y encuestas solo si el cliente los acepta (aviso de privacidad).
 *  - Todo queda registrado en `mensajes`, incluso lo que no se envió y por qué.
 */
class Notificador
{
    public function __construct(private WhatsAppCloud $whatsapp) {}

    /**
     * @param  array<string, string|null>  $variables  variables extra para la plantilla
     */
    public function avisar(EventoMensaje $evento, Cliente $cliente, ?OrdenServicio $orden = null, array $variables = []): Mensaje
    {
        $vars = [...$this->variablesBase($cliente, $orden), ...$variables];
        $base = ['cliente_id' => $cliente->id, 'orden_servicio_id' => $orden?->id, 'evento' => $evento];

        if ($evento->esAdicional() && ! $cliente->acepta_recordatorios) {
            return $this->omitido($base, 'El cliente no acepta recordatorios ni encuestas.');
        }

        $canal = $this->canal($cliente);
        if ($canal === null) {
            return $this->omitido($base, $cliente->canal_preferido === CanalPreferido::Llamada
                ? 'El cliente prefiere llamada: avisar por teléfono.'
                : 'El cliente no tiene WhatsApp ni correo registrados.');
        }

        $plantilla = PlantillaMensaje::where('evento', $evento->value)->where('canal', $canal)->where('activo', true)->first();
        if (! $plantilla) {
            return $this->omitido([...$base, 'canal' => $canal], "No hay plantilla activa de {$canal} para \"{$evento->getLabel()}\".");
        }

        $mensaje = Mensaje::create([
            ...$base,
            'canal' => $canal,
            'destino' => $canal === 'whatsapp' ? $cliente->numeroWhatsapp() : $cliente->email,
            'asunto' => PlantillaMensaje::render($plantilla->asunto, $vars) ?: null,
            'cuerpo' => PlantillaMensaje::render($plantilla->cuerpo, $vars),
            'estado' => EstadoMensaje::Pendiente,
            'meta' => $canal === 'whatsapp' && $plantilla->plantilla_meta ? [
                'plantilla' => $plantilla->plantilla_meta,
                'idioma' => $plantilla->idioma,
                'parametros' => $this->parametrosMeta($plantilla, $vars),
            ] : null,
        ]);

        EnviarMensaje::dispatch($mensaje->id)->afterCommit();

        return $mensaje;
    }

    /**
     * Respuesta libre de un asesor por WhatsApp (solo dentro de la ventana de 24 h).
     */
    public function responder(Cliente $cliente, string $texto, User $asesor, ?OrdenServicio $orden = null): Mensaje
    {
        $mensaje = Mensaje::create([
            'cliente_id' => $cliente->id,
            'orden_servicio_id' => $orden?->id,
            'evento' => EventoMensaje::Respuesta,
            'canal' => 'whatsapp',
            'destino' => $cliente->numeroWhatsapp(),
            'cuerpo' => $texto,
            'estado' => EstadoMensaje::Pendiente,
            'user_id' => $asesor->id,
        ]);

        EnviarMensaje::dispatch($mensaje->id)->afterCommit();

        return $mensaje;
    }

    /**
     * Vuelve a intentar un mensaje fallido.
     */
    public static function reintentar(Mensaje $mensaje): void
    {
        $mensaje->update(['estado' => EstadoMensaje::Pendiente, 'error' => null]);
        EnviarMensaje::dispatch($mensaje->id)->afterCommit();
    }

    /**
     * ¿Se puede escribir texto libre? (el cliente escribió en las últimas 24 h)
     */
    public static function ventanaAbierta(Cliente $cliente): bool
    {
        return Mensaje::where('cliente_id', $cliente->id)->where('direccion', 'entrante')
            ->where('created_at', '>=', now()->subHours(24))->exists();
    }

    private function canal(Cliente $cliente): ?string
    {
        $wa = $this->whatsapp->activo() && filled($cliente->numeroWhatsapp());
        $correo = filled($cliente->email);

        return match ($cliente->canal_preferido) {
            CanalPreferido::Llamada => null,
            CanalPreferido::Correo => $correo ? 'correo' : ($wa ? 'whatsapp' : null),
            default => $wa ? 'whatsapp' : ($correo ? 'correo' : null),
        };
    }

    /**
     * @return array<string, string|null>
     */
    private function variablesBase(Cliente $cliente, ?OrdenServicio $orden): array
    {
        $t = config('tarzo.taller');

        return [
            'cliente' => $cliente->nombre,
            'cliente_completo' => $cliente->nombre_completo,
            'taller' => $t['razon_social'] ?: config('tarzo.nombre_comercial'),
            'telefono_taller' => $t['telefono'],
            'folio' => $orden?->folio,
            'vehiculo' => $orden ? trim($orden->vehiculo->marca.' '.$orden->vehiculo->modelo) : null,
            'placas' => $orden?->vehiculo?->placas,
            'etapa' => $orden?->etapa?->nombre,
            'fecha_promesa' => $orden?->fecha_promesa?->translatedFormat('l j \d\e F'),
            'enlace' => $orden ? EnlacePortal::generar($orden) : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function parametrosMeta(PlantillaMensaje $plantilla, array $vars): array
    {
        return array_map(fn ($v) => (string) ($vars[$v] ?? '-'), $plantilla->variables_meta ?? []);
    }

    private function omitido(array $base, string $motivo): Mensaje
    {
        return Mensaje::create([...$base, 'canal' => $base['canal'] ?? 'ninguno', 'cuerpo' => '', 'estado' => EstadoMensaje::Omitido, 'error' => $motivo]);
    }
}
