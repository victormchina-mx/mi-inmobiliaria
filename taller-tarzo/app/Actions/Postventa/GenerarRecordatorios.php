<?php

namespace App\Actions\Postventa;

use App\Models\OrdenServicio;
use App\Models\Parametro;
use App\Models\Recordatorio;
use App\Models\ReglaMantenimiento;
use App\Models\Vehiculo;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Programa los recordatorios de un mes (se ejecuta el día 1):
 *  - Servicio: según la última entrega del vehículo y cada regla (km o meses, lo que ocurra primero).
 *    El kilometraje se estima con el uso promedio entre visitas (o el valor por defecto de Parámetros).
 *  - Verificación: vehículos cuyo periodo empieza este mes según la terminación de placa.
 */
class GenerarRecordatorios
{
    /**
     * @return int recordatorios nuevos
     */
    public function __invoke(?CarbonInterface $mes = null): int
    {
        $mes = Carbon::parse($mes ?? now())->startOfMonth();
        $fin = $mes->copy()->endOfMonth();
        $reglas = ReglaMantenimiento::where('activo', true)->get();
        $nuevos = 0;

        Vehiculo::query()->with(['cliente', 'ordenes' => fn ($q) => $q->whereNotNull('fecha_entrega')->orderByDesc('fecha_entrega')])
            ->whereHas('ordenes', fn ($q) => $q->whereNotNull('fecha_entrega'))
            ->chunkById(200, function ($vehiculos) use ($reglas, $mes, $fin, &$nuevos) {
                foreach ($vehiculos as $v) {
                    $nuevos += $this->servicio($v, $reglas, $mes, $fin);
                    $nuevos += $this->verificacion($v, $mes);
                }
            });

        return $nuevos;
    }

    private function servicio(Vehiculo $v, $reglas, Carbon $mes, Carbon $fin): int
    {
        /** @var OrdenServicio $ultima */
        $ultima = $v->ordenes->first();
        $kmDia = $this->kmPorDia($v);
        $kmBase = $ultima->kilometraje_ingreso ?? $v->kilometraje;
        $nuevos = 0;

        foreach ($reglas as $regla) {
            $porTiempo = $regla->cada_meses ? $ultima->fecha_entrega->copy()->addMonthsNoOverflow($regla->cada_meses) : null;
            $porKm = $regla->cada_km && $kmDia > 0 ? $ultima->fecha_entrega->copy()->addDays((int) ceil($regla->cada_km / $kmDia)) : null;
            $fecha = collect([$porTiempo, $porKm])->filter()->min();

            if (! $fecha || $fecha->lt($mes) || $fecha->gt($fin)) {
                continue;
            }

            $r = Recordatorio::firstOrCreate(
                ['vehiculo_id' => $v->id, 'referencia' => "regla-{$regla->id}-orden-{$ultima->id}"],
                [
                    'cliente_id' => $v->cliente_id,
                    'regla_mantenimiento_id' => $regla->id,
                    'tipo' => 'servicio',
                    'titulo' => $regla->nombre,
                    'fecha' => $fecha->toDateString(),
                    'km_estimado' => $kmBase ? (int) round($kmBase + $kmDia * $ultima->fecha_entrega->diffInDays($fecha)) : null,
                ],
            );
            $r->wasRecentlyCreated && $nuevos++;
        }

        return $nuevos;
    }

    private function verificacion(Vehiculo $v, Carbon $mes): int
    {
        $periodo = CalendarioVerificacion::periodoQueIniciaEn($v->placas, $mes->month);
        if (! $periodo) {
            return 0;
        }

        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        $r = Recordatorio::firstOrCreate(
            ['vehiculo_id' => $v->id, 'referencia' => "verif-{$mes->year}-{$periodo['periodo']}"],
            [
                'cliente_id' => $v->cliente_id,
                'tipo' => 'verificacion',
                'titulo' => "Verificación (engomado {$periodo['engomado']}): {$meses[$periodo['inicio']]} y {$meses[$periodo['fin']]}",
                'fecha' => $mes->toDateString(),
            ],
        );

        return $r->wasRecentlyCreated ? 1 : 0;
    }

    /**
     * Uso promedio entre las dos últimas visitas con kilometraje; si no hay datos, el valor por defecto.
     */
    public function kmPorDia(Vehiculo $v): float
    {
        $conKm = $v->ordenes->filter(fn ($o) => $o->kilometraje_ingreso)->take(2)->values();

        if ($conKm->count() === 2) {
            $dias = $conKm[1]->fecha_entrega->diffInDays($conKm[0]->fecha_entrega);
            $km = $conKm[0]->kilometraje_ingreso - $conKm[1]->kilometraje_ingreso;
            if ($dias >= 30 && $km > 0) {
                return $km / $dias;
            }
        }

        return Parametro::valor('km_diarios_estimados', 40);
    }
}
