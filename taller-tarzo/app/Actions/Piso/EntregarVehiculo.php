<?php

namespace App\Actions\Piso;

use App\Actions\Ordenes\CambiarEtapa;
use App\Models\Checklist;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Models\User;
use App\Support\Checklists;
use App\Support\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Entrega al cliente: checklist de salida completo y firma de conformidad.
 */
class EntregarVehiculo
{
    /**
     * @param  list<string>  $cumplidos  claves de Checklists::ENTREGA
     */
    public function __invoke(OrdenServicio $orden, array $cumplidos, string $firmaDataUri, string $nombre, User $usuario, ?int $kilometraje = null, ?string $observaciones = null): Checklist
    {
        if ($orden->etapa->clave !== Etapa::LISTO_ENTREGA) {
            throw ValidationException::withMessages(['orden' => 'El vehículo debe estar en "Listo para entrega".']);
        }

        $faltan = array_diff(array_keys(Checklists::ENTREGA), $cumplidos);
        if ($faltan !== []) {
            throw ValidationException::withMessages(['items' => 'Completa todos los puntos de la entrega: falta "'.Checklists::ENTREGA[reset($faltan)].'".']);
        }

        if (mb_strlen(trim($nombre)) < 3) {
            throw ValidationException::withMessages(['nombre' => 'Escribe el nombre de quien recibe.']);
        }

        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $firmaDataUri, $m) || strlen((string) base64_decode($m[1], true)) < 1500) {
            throw ValidationException::withMessages(['firma' => 'Falta la firma de conformidad del cliente.']);
        }

        return DB::transaction(function () use ($orden, $cumplidos, $m, $nombre, $usuario, $kilometraje, $observaciones) {
            $ruta = "entregas/{$orden->folio}-".Str::random(8).'.png';
            Media::disco()->put($ruta, base64_decode($m[1]));

            $checklist = Checklist::create([
                'orden_servicio_id' => $orden->id,
                'tipo' => Checklist::ENTREGA,
                'items' => array_values($cumplidos),
                'aprobado' => true,
                'kilometraje' => $kilometraje,
                'observaciones' => $observaciones,
                'firma' => $ruta,
                'firmado_por' => trim($nombre),
                'user_id' => $usuario->id,
            ]);

            (new CambiarEtapa)($orden, Etapa::where('clave', Etapa::ENTREGADO)->firstOrFail(), $usuario, 'Entregado a '.trim($nombre), interno: true);

            $orden->forceFill(['fecha_entrega' => now(), 'cerrada_at' => now()])->save();

            if ($kilometraje && $kilometraje > (int) $orden->vehiculo->kilometraje) {
                $orden->vehiculo->forceFill(['kilometraje' => $kilometraje])->saveQuietly();
            }

            return $checklist;
        });
    }
}
