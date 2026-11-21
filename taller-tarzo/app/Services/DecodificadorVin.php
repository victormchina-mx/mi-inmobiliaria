<?php

namespace App\Services;

use App\Support\Vin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Decodifica un VIN con el servicio público vPIC de la NHTSA (gratuito, sin llave).
 * Funciona para la mayoría de los vehículos vendidos en Norteamérica; si no hay datos
 * o el servicio no responde, devuelve null y el asesor captura a mano.
 */
class DecodificadorVin
{
    public const URL = 'https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValues/%s?format=json';

    /**
     * @return array{marca:?string, modelo:?string, anio:?int, motor:?string, transmision:?string, version:?string}|null
     */
    public function decodificar(string $vin): ?array
    {
        $vin = Vin::normalizar($vin);

        if (! Vin::formatoValido($vin)) {
            return null;
        }

        return Cache::remember("vin:{$vin}", now()->addDays(30), function () use ($vin) {
            try {
                $respuesta = Http::timeout(8)->retry(1, 300)->get(sprintf(self::URL, $vin));
            } catch (Throwable) {
                return null;
            }

            $r = $respuesta->successful() ? ($respuesta->json('Results.0') ?? []) : [];

            if (blank($r['Make'] ?? null)) {
                return null;
            }

            $cilindros = $r['EngineCylinders'] ?? null;
            $litros = isset($r['DisplacementL']) && $r['DisplacementL'] !== '' ? round((float) $r['DisplacementL'], 1).' L' : null;

            return [
                'marca' => mb_convert_case(mb_strtolower($r['Make']), MB_CASE_TITLE),
                'modelo' => $r['Model'] ?: null,
                'anio' => is_numeric($r['ModelYear'] ?? null) ? (int) $r['ModelYear'] : null,
                'motor' => trim(implode(' ', array_filter([$litros, $cilindros ? "{$cilindros} cil." : null]))) ?: null,
                'transmision' => match (true) {
                    str_contains(strtolower($r['TransmissionStyle'] ?? ''), 'manual') => 'Estándar',
                    filled($r['TransmissionStyle'] ?? null) => 'Automática',
                    default => null,
                },
                'version' => $r['Trim'] ?: null,
            ];
        });
    }
}
