<?php

namespace App\Actions\Almacen;

use App\Enums\EstadoOrdenCompra;
use App\Enums\EstadoRequisicion;
use App\Models\OrdenCompra;
use App\Models\OrdenServicio;
use App\Models\Recepcion;
use App\Models\Requisicion;
use App\Models\User;
use App\Services\Inventario\Inventario;
use App\Services\Inventario\LectorCfdi;
use App\Support\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recepción de mercancía de una orden de compra (total o parcial).
 *  - Productos de stock: entrada al inventario al costo real.
 *  - Piezas para una orden: la requisición queda recibida y el costo real pasa a la partida.
 *  - Si una orden ya tiene todas sus piezas, sale sola de "Espera de refacciones".
 */
class RecibirMercancia
{
    /**
     * @param  array<int|string, array{cantidad?: float|string|null, costo_real?: float|string|null}>  $lineas  orden_compra_partida_id => datos
     */
    public function __invoke(OrdenCompra $oc, array $lineas, ?User $usuario = null, ?string $facturaXml = null, ?string $foto = null, ?string $notas = null): Recepcion
    {
        if (! $oc->estado->recibible()) {
            throw ValidationException::withMessages(['orden_compra' => 'Esta orden de compra no está en espera de mercancía.']);
        }

        $factura = $facturaXml ? app(LectorCfdi::class)->leer(Media::disco()->get($facturaXml)) : null;

        if ($factura && $factura['uuid'] && Recepcion::where('factura_uuid', $factura['uuid'])->exists()) {
            throw ValidationException::withMessages(['factura' => 'Esta factura ya se registró en otra recepción.']);
        }

        $ordenesAfectadas = [];

        $recepcion = DB::transaction(function () use ($oc, $lineas, $usuario, $facturaXml, $foto, $notas, $factura, &$ordenesAfectadas) {
            $recepcion = Recepcion::create([
                'orden_compra_id' => $oc->id,
                'user_id' => $usuario?->id,
                'factura_xml' => $facturaXml,
                'factura_uuid' => $factura['uuid'] ?? null,
                'factura_total' => $factura['total'] ?? null,
                'foto' => $foto,
                'notas' => $notas,
            ]);

            $alguna = false;

            foreach ($oc->partidas()->with(['requisicion', 'producto'])->get() as $p) {
                $cantidad = (float) ($lineas[$p->id]['cantidad'] ?? 0);
                if ($cantidad <= 0) {
                    continue;
                }

                if ($cantidad > $p->pendiente() + 0.0005) {
                    throw ValidationException::withMessages(['cantidad' => "{$p->descripcion}: solo faltan {$p->pendiente()} por recibir."]);
                }

                $costo = filled($lineas[$p->id]['costo_real'] ?? null) ? (float) $lineas[$p->id]['costo_real'] : $p->costo_unitario;
                $p->forceFill(['cantidad_recibida' => round($p->cantidad_recibida + $cantidad, 3), 'costo_real' => $costo])->save();
                $alguna = true;

                if ($p->producto) {
                    app(Inventario::class)->entrada($p->producto, $cantidad, $costo, $usuario, $oc->folio, $recepcion->id);
                }

                if ($p->requisicion && $p->pendiente() <= 0.0005) {
                    $this->marcarRecibida($p->requisicion, $costo);
                    $ordenesAfectadas[$p->requisicion->orden_servicio_id] = true;
                }
            }

            if (! $alguna) {
                throw ValidationException::withMessages(['cantidad' => 'Indica la cantidad recibida de al menos una partida.']);
            }

            $completa = $oc->partidas()->get()->every(fn ($p) => $p->pendiente() <= 0.0005);
            $oc->forceFill([
                'estado' => $completa ? EstadoOrdenCompra::Recibida : EstadoOrdenCompra::RecibidaParcial,
                'recibida_at' => $completa ? now() : null,
            ])->save();

            return $recepcion;
        });

        foreach (array_keys($ordenesAfectadas) as $ordenId) {
            app(VerificarRefacciones::class)(OrdenServicio::find($ordenId));
        }

        return $recepcion;
    }

    /**
     * Pieza que surte la aseguradora: se marca recibida sin orden de compra.
     */
    public function deAseguradora(Requisicion $requisicion): void
    {
        if ($requisicion->estado === EstadoRequisicion::Recibida) {
            return;
        }

        $this->marcarRecibida($requisicion, null);
        app(VerificarRefacciones::class)($requisicion->orden);
    }

    private function marcarRecibida(Requisicion $r, ?float $costo): void
    {
        $r->forceFill(['estado' => EstadoRequisicion::Recibida, 'recibida_at' => now(), 'costo_real' => $costo])->save();

        if ($costo !== null && $r->partida) {
            $r->partida->forceFill(['costo_real' => $costo])->saveQuietly();
        }
    }
}
