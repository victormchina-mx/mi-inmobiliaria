<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Filament\Resources\Productos\ProductoResource;
use App\Models\MovimientoInventario;
use App\Models\OrdenServicio;
use App\Models\Producto;
use App\Models\User;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto que modifica existencias. Costo promedio ponderado:
 *   nuevo promedio = (existencia × promedio + cantidad × costo) ÷ (existencia + cantidad)
 * Las salidas, ajustes y devoluciones se valoran al costo promedio vigente.
 * Toda salida va a una orden de servicio o a gasto general (con motivo).
 */
class Inventario
{
    public function entrada(Producto $producto, float $cantidad, float $costoUnitario, ?User $usuario = null, ?string $referencia = null, ?int $recepcionId = null): MovimientoInventario
    {
        $this->exigirPositiva($cantidad);

        if ($costoUnitario < 0) {
            throw ValidationException::withMessages(['costo' => 'El costo no puede ser negativo.']);
        }

        return $this->registrar($producto, TipoMovimiento::Entrada, $cantidad, $costoUnitario, $usuario, [
            'referencia' => $referencia,
            'recepcion_id' => $recepcionId,
        ], recalculaPromedio: true);
    }

    public function salida(Producto $producto, float $cantidad, ?OrdenServicio $orden, ?User $usuario = null, ?string $motivoGastoGeneral = null): MovimientoInventario
    {
        $this->exigirPositiva($cantidad);

        if (! $orden && blank($motivoGastoGeneral)) {
            throw ValidationException::withMessages(['orden' => 'Toda salida debe cargarse a una orden de servicio o a gasto general con un motivo.']);
        }

        return $this->registrar($producto, TipoMovimiento::Salida, -$cantidad, null, $usuario, [
            'orden_servicio_id' => $orden?->id,
            'gasto_general' => $orden === null,
            'referencia' => $orden ? $orden->folio : $motivoGastoGeneral,
        ]);
    }

    public function devolucion(Producto $producto, float $cantidad, OrdenServicio $orden, ?User $usuario = null): MovimientoInventario
    {
        $this->exigirPositiva($cantidad);

        $sacado = -1 * (float) MovimientoInventario::where('producto_id', $producto->id)->where('orden_servicio_id', $orden->id)->sum('cantidad');
        if ($cantidad > $sacado + 0.0001) {
            throw ValidationException::withMessages(['cantidad' => "Solo se pueden devolver {$sacado} {$producto->unidad->getLabel()} de esta orden."]);
        }

        return $this->registrar($producto, TipoMovimiento::Devolucion, $cantidad, null, $usuario, [
            'orden_servicio_id' => $orden->id,
            'referencia' => 'Devolución de '.$orden->folio,
        ]);
    }

    /**
     * Ajusta la existencia a la cantidad contada (conteo físico).
     */
    public function ajustarA(Producto $producto, float $contado, ?User $usuario = null, string $motivo = 'Conteo físico'): ?MovimientoInventario
    {
        if ($contado < 0) {
            throw ValidationException::withMessages(['contado' => 'La cantidad contada no puede ser negativa.']);
        }

        $diferencia = round($contado - $producto->fresh()->existencia, 3);

        return abs($diferencia) < 0.0005
            ? null
            : $this->registrar($producto, TipoMovimiento::Ajuste, $diferencia, null, $usuario, ['referencia' => $motivo]);
    }

    private function registrar(Producto $producto, TipoMovimiento $tipo, float $cantidad, ?float $costoUnitario, ?User $usuario, array $extra, bool $recalculaPromedio = false): MovimientoInventario
    {
        [$movimiento, $cruzoMinimo] = DB::transaction(function () use ($producto, $tipo, $cantidad, $costoUnitario, $usuario, $extra, $recalculaPromedio) {
            /** @var Producto $p */
            $p = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $estabaBajo = $p->bajoMinimo();

            $nueva = round($p->existencia + $cantidad, 3);
            if ($nueva < -0.0005) {
                throw ValidationException::withMessages(['cantidad' => "No hay suficiente {$p->nombre}: existencia {$p->existencia} {$p->unidad->getLabel()}."]);
            }

            $costo = $costoUnitario ?? $p->costo_promedio;
            $promedio = $p->costo_promedio;
            if ($recalculaPromedio) {
                $promedio = $p->existencia > 0
                    ? ($p->existencia * $p->costo_promedio + $cantidad * $costo) / ($p->existencia + $cantidad)
                    : $costo;
            }

            $movimiento = MovimientoInventario::create([
                'producto_id' => $p->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'costo_unitario' => round($costo, 4),
                'costo_total' => round(abs($cantidad) * $costo, 2),
                'existencia_resultante' => $nueva,
                'costo_promedio_resultante' => round($promedio, 4),
                'user_id' => $usuario?->id,
                ...$extra,
            ]);

            $p->forceFill([
                'existencia' => max(0, $nueva),
                'costo_promedio' => round($promedio, 4),
                'ultimo_movimiento_at' => now(),
            ]);

            $quedaBajo = $p->bajoMinimo();
            $cruzo = ! $estabaBajo && $quedaBajo && $p->alerta_minimo_at === null;
            $p->alerta_minimo_at = $quedaBajo ? ($p->alerta_minimo_at ?? now()) : null;
            $p->save();

            return [$movimiento, $cruzo ? $p : null];
        });

        $producto->refresh();

        if ($cruzoMinimo) {
            $this->avisarMinimo($cruzoMinimo);
        }

        return $movimiento;
    }

    private function avisarMinimo(Producto $p): void
    {
        Avisos::a('almacen.gestionar', Notification::make()
            ->title("Stock bajo: {$p->nombre}")
            ->body("Quedan {$p->existencia} {$p->unidad->getLabel()} (mínimo {$p->stock_minimo}). Sugerido pedir: {$p->sugerenciaCompra()}.")
            ->icon('heroicon-o-exclamation-triangle')->warning()
            ->actions([Action::make('ver')->label('Ver producto')->url(ProductoResource::getUrl('edit', ['record' => $p], panel: 'taller'))]));
    }

    private function exigirPositiva(float $cantidad): void
    {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor a cero.']);
        }
    }
}
