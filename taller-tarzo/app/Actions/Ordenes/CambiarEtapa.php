<?php

namespace App\Actions\Ordenes;

use App\Enums\EventoMensaje;
use App\Enums\TipoOrden;
use App\Models\Etapa;
use App\Models\HistorialEtapa;
use App\Models\OrdenServicio;
use App\Models\User;
use App\Services\Comunicacion\Notificador;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mueve una orden a otra etapa del tablero, aplica las reglas de la etapa y deja registro.
 *
 * Reglas (configurables en Catálogos → Etapas):
 *  - "Requiere cotización autorizada": no se entra sin presupuesto aprobado (garantías e internas exentas).
 *  - "Checklist para entrar": Listo para entrega exige calidad aprobada; Entregado exige checklist de entrega.
 *  - "Evidencia para salir": en órdenes de seguro, no se avanza sin al menos una foto de esa etapa.
 *  - Límite de autos simultáneos por etapa.
 * Quien tiene el permiso piso.forzar_etapa puede saltarse las reglas con un comentario; queda marcado.
 */
class CambiarEtapa
{
    public function __invoke(OrdenServicio $orden, Etapa $destino, ?User $usuario = null, ?string $comentario = null, bool $forzar = false, bool $interno = false): OrdenServicio
    {
        // $interno: lo llama otro proceso (calidad, entrega) que ya validó sus propios permisos.
        if ($usuario && ! $interno && ! $usuario->can('piso.gestionar')) {
            throw new AuthorizationException('No tienes permiso para mover órdenes en el tablero.');
        }

        if ($forzar && $usuario && ! $usuario->can('piso.forzar_etapa')) {
            throw new AuthorizationException('No tienes permiso para mover órdenes saltándose las reglas.');
        }

        if ($orden->etapa_id === $destino->id) {
            return $orden;
        }

        if ($forzar && blank($comentario)) {
            throw ValidationException::withMessages(['comentario' => 'Explica por qué se salta la regla.']);
        }

        if (! $forzar) {
            $problemas = self::problemas($orden, $destino);
            if ($problemas !== []) {
                throw ValidationException::withMessages(['etapa' => $problemas]);
            }
        }

        $orden = DB::transaction(function () use ($orden, $destino, $usuario, $comentario, $forzar) {
            $anterior = $orden->etapa_id;

            $orden->forceFill([
                'etapa_id' => $destino->id,
                'etapa_desde' => now(),
            ])->save();

            HistorialEtapa::create([
                'orden_servicio_id' => $orden->id,
                'etapa_anterior_id' => $anterior,
                'etapa_id' => $destino->id,
                'user_id' => $usuario?->id,
                'comentario' => $comentario,
                'excepcion' => $forzar,
            ]);

            return $orden->refresh();
        });

        $this->avisarCliente($orden, $destino);

        return $orden;
    }

    /**
     * Motivos por los que la orden no puede entrar a la etapa (vacío si puede).
     *
     * @return list<string>
     */
    public static function problemas(OrdenServicio $orden, Etapa $destino): array
    {
        $orden->loadMissing('etapa');
        $origen = $orden->etapa;
        $problemas = [];

        $exenta = in_array($orden->tipo, [TipoOrden::Garantia, TipoOrden::Interna], true);
        if ($destino->requiere_cotizacion_aprobada && ! $exenta && ! $orden->tieneCotizacionAprobada()) {
            $problemas[] = "Para pasar a {$destino->nombre} la cotización debe estar autorizada.";
        }

        if ($destino->requiere_checklist && ! $orden->checklist($destino->requiere_checklist)?->aprobado) {
            $problemas[] = $destino->requiere_checklist === 'entrega'
                ? 'Para entregar se necesita el checklist de entrega firmado por el cliente.'
                : "Para pasar a {$destino->nombre} el control de calidad debe estar aprobado.";
        }

        $avanza = $origen && $destino->orden > $origen->orden;
        if ($avanza && $origen->requiere_evidencia && $orden->tipo === TipoOrden::Seguro
            && $orden->evidencias()->where('etapa_id', $origen->id)->doesntExist()) {
            $problemas[] = "Sube al menos una foto de evidencia de {$origen->nombre} antes de avanzar (la pide la aseguradora).";
        }

        if ($destino->limite_autos) {
            $ocupados = OrdenServicio::where('etapa_id', $destino->id)->whereNull('cerrada_at')->count();
            if ($ocupados >= $destino->limite_autos) {
                $problemas[] = "{$destino->nombre} ya tiene su límite de {$destino->limite_autos} autos.";
            }
        }

        return $problemas;
    }

    private function avisarCliente(OrdenServicio $orden, Etapa $destino): void
    {
        if (! $destino->notifica_cliente || ! $orden->cliente) {
            return;
        }

        $evento = match ($destino->clave) {
            Etapa::LISTO_ENTREGA => EventoMensaje::ListoEntrega,
            Etapa::ENTREGADO => EventoMensaje::Entregado,
            default => EventoMensaje::CambioEtapa,
        };

        // WhatsApp o correo según la preferencia del cliente (queda en la bitácora de mensajes).
        app(Notificador::class)->avisar($evento, $orden->cliente, $orden);
    }
}
