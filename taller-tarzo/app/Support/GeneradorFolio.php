<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Genera folios consecutivos por serie y año, p. ej. OS-2027-000123.
 * Usa bloqueo de fila para no repetir folios con usuarios simultáneos.
 */
final class GeneradorFolio
{
    public static function siguiente(string $serie = 'OS', ?int $anio = null): string
    {
        $anio ??= (int) now()->format('Y');

        return sprintf('%s-%d-%06d', $serie, $anio, self::numero($serie, $anio));
    }

    /**
     * Consecutivo numérico. Con $anio = 0 la numeración no se reinicia cada año (folios de CFDI).
     */
    public static function numero(string $serie, int $anio = 0): int
    {
        return DB::transaction(function () use ($serie, $anio) {
            $fila = DB::table('consecutivos')
                ->where('serie', $serie)
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            if ($fila === null) {
                DB::table('consecutivos')->insert(['serie' => $serie, 'anio' => $anio, 'ultimo' => 1]);

                return 1;
            }

            $siguiente = $fila->ultimo + 1;
            DB::table('consecutivos')->where('id', $fila->id)->update(['ultimo' => $siguiente]);

            return $siguiente;
        });
    }
}
