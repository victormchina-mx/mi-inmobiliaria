<?php

namespace App\Actions\Almacen;

use App\Enums\EstadoOrdenCompra;
use App\Enums\EstadoRequisicion;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CambiarEstadoCompra
{
    public function enviar(OrdenCompra $oc): OrdenCompra
    {
        $this->exigir($oc, [EstadoOrdenCompra::Borrador]);

        if ($oc->partidas()->doesntExist()) {
            throw ValidationException::withMessages(['orden_compra' => 'La orden de compra no tiene partidas.']);
        }

        $oc->forceFill(['estado' => EstadoOrdenCompra::Enviada, 'enviada_at' => now()])->save();

        return $oc;
    }

    public function confirmar(OrdenCompra $oc, ?string $fechaEstimada = null): OrdenCompra
    {
        $this->exigir($oc, [EstadoOrdenCompra::Enviada, EstadoOrdenCompra::Confirmada]);
        $oc->forceFill(['estado' => EstadoOrdenCompra::Confirmada, 'fecha_estimada' => $fechaEstimada ?? $oc->fecha_estimada, 'alerta_retraso_at' => null])->save();

        return $oc;
    }

    public function enTransito(OrdenCompra $oc): OrdenCompra
    {
        $this->exigir($oc, [EstadoOrdenCompra::Enviada, EstadoOrdenCompra::Confirmada]);
        $oc->forceFill(['estado' => EstadoOrdenCompra::EnTransito])->save();

        return $oc;
    }

    public function cancelar(OrdenCompra $oc): OrdenCompra
    {
        $this->exigir($oc, [EstadoOrdenCompra::Borrador, EstadoOrdenCompra::Enviada, EstadoOrdenCompra::Confirmada, EstadoOrdenCompra::EnTransito]);

        return DB::transaction(function () use ($oc) {
            foreach ($oc->partidas()->with('requisicion')->get() as $p) {
                if ($p->requisicion?->estado === EstadoRequisicion::Ordenada) {
                    $p->requisicion->update(['estado' => EstadoRequisicion::Pendiente]); // vuelve a "por pedir"
                }
            }
            $oc->forceFill(['estado' => EstadoOrdenCompra::Cancelada])->save();

            return $oc;
        });
    }

    /**
     * @param  list<EstadoOrdenCompra>  $permitidos
     */
    private function exigir(OrdenCompra $oc, array $permitidos): void
    {
        if (! in_array($oc->estado, $permitidos, true)) {
            throw ValidationException::withMessages(['orden_compra' => "No se puede desde el estado \"{$oc->estado->getLabel()}\"."]);
        }
    }
}
