<?php

namespace App\Actions\Piso;

use App\Actions\Ordenes\CambiarEtapa;
use App\Models\Checklist;
use App\Models\Etapa;
use App\Models\OrdenServicio;
use App\Models\User;
use App\Support\Checklists;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Control de calidad: si se cumplen todos los puntos, la orden pasa a "Listo para entrega";
 * si no, regresa a la etapa indicada para corregir.
 */
class RegistrarCalidad
{
    /**
     * @param  list<string>  $cumplidos  claves de Checklists::CALIDAD
     */
    public function __invoke(OrdenServicio $orden, array $cumplidos, User $usuario, ?string $observaciones = null, ?Etapa $regresarA = null): Checklist
    {
        if ($orden->etapa->clave !== Etapa::CALIDAD) {
            throw ValidationException::withMessages(['orden' => 'La orden no está en control de calidad.']);
        }

        $cumplidos = array_values(array_intersect($cumplidos, array_keys(Checklists::CALIDAD)));
        $aprobado = count($cumplidos) === count(Checklists::CALIDAD);

        if (! $aprobado && ! $regresarA) {
            throw ValidationException::withMessages(['regresar' => 'Indica a qué etapa regresa el auto para corregir.']);
        }

        if (! $aprobado && blank($observaciones)) {
            throw ValidationException::withMessages(['observaciones' => 'Describe qué hay que corregir.']);
        }

        return DB::transaction(function () use ($orden, $cumplidos, $usuario, $observaciones, $regresarA, $aprobado) {
            $checklist = Checklist::create([
                'orden_servicio_id' => $orden->id,
                'tipo' => Checklist::CALIDAD,
                'items' => $cumplidos,
                'aprobado' => $aprobado,
                'observaciones' => $observaciones,
                'user_id' => $usuario->id,
            ]);

            $aprobado
                ? (new CambiarEtapa)($orden, Etapa::where('clave', Etapa::LISTO_ENTREGA)->firstOrFail(), $usuario, 'Control de calidad aprobado', interno: true)
                : (new CambiarEtapa)($orden, $regresarA, $usuario, 'Regresa de calidad: '.$observaciones, interno: true);

            return $checklist;
        });
    }
}
