<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

/**
 * Punto exacto de una propiedad en el mapa (columnas lat, lng y
 * exact_location de la tabla property; ver app/Database/sql/).
 *
 * - El agente o el admin lo marca en el formulario (partials/location_picker).
 * - Si exact_location = 1, los visitantes ven el punto exacto.
 * - Si no, ven una zona de APPROX_RADIUS metros cuyo centro está desplazado
 *   entre 100 y 200 m del punto real, siempre igual para cada propiedad:
 *   así la zona no "apunta" a la casa y no cambia de lugar en cada visita.
 * - Sin punto marcado, todo sigue como antes (Geocoder con la dirección).
 *
 * Si la base de datos todavía no tiene las columnas, ready() es false y
 * no se intenta guardarlas (para que el sitio no falle antes de correr el SQL).
 */
class PropertyLocation
{
    /** Radio de la zona aproximada, en metros. */
    public const APPROX_RADIUS = 300;

    private static ?bool $ready = null;

    /** ¿Ya existen las columnas lat/lng/exact_location en la tabla property? */
    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = db_connect()->fieldExists('lat', 'property')
                    && db_connect()->fieldExists('exact_location', 'property');
            } catch (Throwable) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /**
     * Datos a guardar a partir del formulario o de la API.
     *
     * @param bool $onlyIfSent API: si la app no manda "lat", no se toca lo guardado
     *
     * @return array{lat?: float|null, lng?: float|null, exact_location?: int}
     */
    public static function fromRequest(IncomingRequest $request, bool $onlyIfSent = false): array
    {
        if (! self::ready() || ($onlyIfSent && $request->getPost('lat') === null)) {
            return [];
        }

        $point = self::parse($request->getPost('lat'), $request->getPost('lng'));

        return [
            'lat'            => $point['lat'] ?? null,
            'lng'            => $point['lng'] ?? null,
            'exact_location' => $point !== null && (string) $request->getPost('exact_location') === '1' ? 1 : 0,
        ];
    }

    /**
     * Valida un par de coordenadas (texto del formulario).
     *
     * @return array{lat: float, lng: float}|null null si están vacías o no son válidas
     */
    public static function parse(mixed $lat, mixed $lng): ?array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = round((float) $lat, 6);
        $lng = round((float) $lng, 6);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat === 0.0 && $lng === 0.0)) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * El punto marcado de una propiedad, tal como está guardado.
     *
     * @return array{lat: float, lng: float, exact: bool}|null
     */
    public static function point(array $property): ?array
    {
        $point = self::parse($property['lat'] ?? null, $property['lng'] ?? null);

        return $point === null ? null : $point + ['exact' => ! empty($property['exact_location'])];
    }

    /**
     * Lo que ven los visitantes: el punto exacto, o el centro (desplazado)
     * de la zona aproximada.
     *
     * @return array{lat: float, lng: float, level: string, bbox: null}|null
     *         level: 'exact' | 'approx'
     */
    public static function public(array $property): ?array
    {
        $point = self::point($property);

        if ($point === null) {
            return null;
        }

        if ($point['exact']) {
            return ['lat' => $point['lat'], 'lng' => $point['lng'], 'level' => 'exact', 'bbox' => null];
        }

        // Desplazamiento estable: el mismo para la misma propiedad
        $seed     = crc32('vmch-zona-' . ($property['pid'] ?? '') . '|' . $point['lat'] . '|' . $point['lng']);
        $angle    = deg2rad($seed % 360);
        $distance = 100 + ($seed >> 9) % 101; // 100–200 m

        return [
            'lat'   => round($point['lat'] + ($distance * cos($angle)) / 111320, 6),
            'lng'   => round($point['lng'] + ($distance * sin($angle)) / (111320 * max(0.2, cos(deg2rad($point['lat'])))), 6),
            'level' => 'approx',
            'bbox'  => null,
        ];
    }
}
