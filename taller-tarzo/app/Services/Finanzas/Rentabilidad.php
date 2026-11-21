<?php

namespace App\Services\Finanzas;

use App\Enums\EstadoFactura;
use App\Enums\EstadoPartida;
use App\Enums\EstadoRequisicion;
use App\Enums\TipoMovimiento;
use App\Filament\Resources\OrdenServicios\OrdenServicioResource;
use App\Models\CierreOrden;
use App\Models\Factura;
use App\Models\OrdenServicio;
use App\Models\Parametro;
use App\Models\RegistroTiempo;
use App\Models\User;
use App\Services\Cotizacion\Contexto;
use App\Services\Cotizacion\Cotizador;
use App\Support\Avisos;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Utilidad real de una orden (todo sin IVA):
 *
 *   utilidad = ingreso facturado (facturas timbradas − notas de crédito)
 *            − refacciones (requisiciones surtidas por el taller × costo real del XML del proveedor)
 *            − materiales y consumibles (salidas de almacén − devoluciones, a costo promedio)
 *            − mano de obra (minutos reales de cada técnico × su costo de hora-hombre)
 *            − subcontratos (grúa, rectificadora, cristales…)
 *   margen % = utilidad ÷ ingreso × 100
 *
 * Sin facturas, el ingreso se estima con las partidas autorizadas (queda marcado como estimado).
 */
class Rentabilidad
{
    /**
     * @return array<string, float|bool|null>
     */
    public function calcular(OrdenServicio $orden): array
    {
        [$ingreso, $estimado] = $this->ingreso($orden);

        $refacciones = $orden->requisiciones()->with('partida')
            ->where('estado', '!=', EstadoRequisicion::Cancelada->value)->where('surte', 'taller')->get()
            ->sum(fn ($r) => (float) $r->cantidad * (float) ($r->costo_real ?? $r->partida?->costo_unitario ?? 0));

        $mov = $orden->consumos()->get();
        $materiales = $mov->where('tipo', TipoMovimiento::Salida)->sum('costo_total') - $mov->where('tipo', TipoMovimiento::Devolucion)->sum('costo_total');

        $registros = RegistroTiempo::with('tecnico')->whereNotNull('fin')
            ->whereHas('tarea', fn ($query) => $query->where('orden_servicio_id', $orden->id))->get();
        $manoObra = $registros->sum(fn ($r) => $r->minutos / 60 * (float) ($r->tecnico?->costo_hora ?? 0));

        $subcontratos = (float) $orden->gastos()->sum('monto');
        $utilidad = $ingreso - $refacciones - $materiales - $manoObra - $subcontratos;

        return [
            'ingreso' => round($ingreso, 2),
            'ingreso_estimado' => $estimado,
            'costo_refacciones' => round($refacciones, 2),
            'costo_materiales' => round(abs($materiales) < 0.005 ? 0 : $materiales, 2),
            'costo_mano_obra' => round($manoObra, 2),
            'costo_subcontratos' => round($subcontratos, 2),
            'utilidad' => round($utilidad, 2),
            'margen' => $ingreso > 0 ? round($utilidad / $ingreso * 100, 2) : null,
            'horas_reales' => round($registros->sum('minutos') / 60, 2),
            'horas_estandar' => round((float) $orden->tareas()->sum('horas_estandar'), 2),
        ];
    }

    /**
     * Guarda (o actualiza) la utilidad de la orden y avisa si el margen queda por debajo del mínimo.
     */
    public function cerrar(OrdenServicio $orden, ?User $usuario = null): CierreOrden
    {
        $datos = $this->calcular($orden);
        $anterior = $orden->cierre()->first();

        $cierre = CierreOrden::updateOrCreate(['orden_servicio_id' => $orden->id], [...$datos, 'calculado_at' => now(), 'user_id' => $usuario?->id]);

        $minimo = Parametro::valor('margen_minimo', 25);
        $bajo = $datos['margen'] !== null && $datos['margen'] < $minimo;
        $antesBajo = $anterior && $anterior->margen !== null && $anterior->margen < $minimo;

        if ($bajo && ! $antesBajo) {
            Avisos::a('reportes.gestionar', Notification::make()
                ->title("Margen bajo en {$orden->folio}: ".number_format($datos['margen'], 1).' %')
                ->body('Utilidad $'.number_format($datos['utilidad'], 2).' sobre $'.number_format($datos['ingreso'], 2)." (mínimo {$minimo} %).".($datos['ingreso_estimado'] ? ' Ingreso estimado: aún no se factura.' : ''))
                ->icon('heroicon-o-arrow-trending-down')->warning()
                ->actions([Action::make('ver')->label('Ver orden')->url(OrdenServicioResource::getUrl('view', ['record' => $orden], panel: 'taller'))]));
        }

        return $cierre;
    }

    /**
     * @return array{0: float, 1: bool}
     */
    private function ingreso(OrdenServicio $orden): array
    {
        $facturas = $orden->facturas()->whereIn('tipo', [Factura::INGRESO, Factura::EGRESO])
            ->where('estado', EstadoFactura::Timbrada->value)->get();

        if ($facturas->where('tipo', Factura::INGRESO)->isNotEmpty()) {
            $neto = $facturas->sum(fn (Factura $f) => ($f->tipo === Factura::EGRESO ? -1 : 1) * ($f->subtotal - $f->descuento));

            return [(float) $neto, false];
        }

        $c = $orden->cotizacionActual()->first();
        $aprobadas = $c?->partidas()->where('estado', EstadoPartida::Aprobada->value)->get();
        if (! $aprobadas || $aprobadas->isEmpty()) {
            return [0.0, true];
        }

        $t = app(Cotizador::class)->totales($aprobadas->map->toArray(), Contexto::paraCotizacion($c));
        $tasa = (float) ($c->iva_tasa ?: 0.16);

        return [($t['monto_aseguradora'] + $t['monto_cliente']) / (1 + $tasa), true];
    }
}
