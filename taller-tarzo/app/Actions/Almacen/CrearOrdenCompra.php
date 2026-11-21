<?php

namespace App\Actions\Almacen;

use App\Enums\EstadoOrdenCompra;
use App\Enums\EstadoRequisicion;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\User;
use App\Support\GeneradorFolio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agrupa piezas por pedir (o productos bajo mínimo) en una orden de compra a un proveedor.
 */
class CrearOrdenCompra
{
    /**
     * @param  Collection<int, Requisicion>  $requisiciones
     */
    public function desdeRequisiciones(Collection $requisiciones, Proveedor $proveedor, ?User $usuario = null): OrdenCompra
    {
        $validas = $requisiciones->filter(fn (Requisicion $r) => $r->estado === EstadoRequisicion::Pendiente && $r->surte === 'taller');

        if ($validas->isEmpty()) {
            throw ValidationException::withMessages(['requisiciones' => 'Selecciona piezas "por pedir" que surta el taller.']);
        }

        return DB::transaction(function () use ($validas, $proveedor, $usuario) {
            $oc = $this->nueva($proveedor, $usuario);

            foreach ($validas as $r) {
                $r->loadMissing('partida');
                $oc->partidas()->create([
                    'requisicion_id' => $r->id,
                    'producto_id' => $r->producto_id,
                    'descripcion' => $r->descripcion.' · '.$r->orden->folio,
                    'numero_parte' => $r->numero_parte,
                    'cantidad' => $r->cantidad,
                    'costo_unitario' => $r->partida?->costo_unitario ?? 0,
                ]);
                $r->update(['estado' => EstadoRequisicion::Ordenada]);
            }

            $oc->recalcularSubtotal();

            return $oc;
        });
    }

    /**
     * @param  Collection<int, Producto>  $productos
     */
    public function reposicion(Collection $productos, Proveedor $proveedor, ?User $usuario = null): OrdenCompra
    {
        $productos = $productos->filter(fn (Producto $p) => $p->sugerenciaCompra() > 0);

        if ($productos->isEmpty()) {
            throw ValidationException::withMessages(['productos' => 'Ninguno de los productos necesita reposición.']);
        }

        return DB::transaction(function () use ($productos, $proveedor, $usuario) {
            $oc = $this->nueva($proveedor, $usuario);

            foreach ($productos as $p) {
                $oc->partidas()->create([
                    'producto_id' => $p->id,
                    'descripcion' => $p->nombre,
                    'numero_parte' => $p->sku,
                    'cantidad' => $p->sugerenciaCompra(),
                    'costo_unitario' => (float) $p->costo_promedio,
                ]);
            }

            $oc->recalcularSubtotal();

            return $oc;
        });
    }

    private function nueva(Proveedor $proveedor, ?User $usuario): OrdenCompra
    {
        return OrdenCompra::create([
            'folio' => GeneradorFolio::siguiente('OC'),
            'proveedor_id' => $proveedor->id,
            'estado' => EstadoOrdenCompra::Borrador,
            'fecha_estimada' => today()->addDays($proveedor->dias_entrega),
            'user_id' => $usuario?->id,
        ]);
    }
}
