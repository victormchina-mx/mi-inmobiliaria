<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * Ubica una propiedad en el mapa a partir de su dirección en texto
 * (colonia/calle, ciudad y estado), con OpenStreetMap Nominatim.
 *
 * Las propiedades no guardan coordenadas, así que se prueba de lo más
 * preciso a lo más general y se devuelve hasta dónde se pudo ubicar:
 *   1. "location, city, state"  → nivel "location" (zona/colonia)
 *   2. "city, state"            → nivel "city"
 *   3. "state"                  → nivel "state"
 *
 * Cada búsqueda se guarda en caché (las encontradas 90 días, las no
 * encontradas 1 día): Nominatim pide no repetir consultas y como máximo
 * una por segundo, y así la página de ubicación responde al instante.
 * Política de uso: https://operations.osmfoundation.org/policies/nominatim/
 *
 * Vista: app/Views/property/location.php
 */
class Geocoder
{
    private const ENDPOINT   = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'InmobiliariaVMCH/1.0 (+https://inmobiliaria.vmch.net)';
    private const TIMEOUT    = 5;
    private const TTL_FOUND  = 90 * DAY;
    private const TTL_MISS   = DAY;
    private const TTL_DOWN   = 10 * MINUTE; // Nominatim no respondió: no reintentar en cada visita

    /**
     * @return array{lat: float, lng: float, level: string, bbox: list<float>|null}|null
     *         bbox = [sur, norte, oeste, este]
     */
    public function locate(array $property): ?array
    {
        foreach (self::queries($property) as $level => $query) {
            $hit = $this->search($query);
            if ($hit === false) {
                return null; // sin conexión: no seguir esperando con los demás niveles
            }
            if ($hit !== null) {
                return $hit + ['level' => $level];
            }
        }

        return null;
    }

    /**
     * Textos a buscar, de lo más preciso a lo más general, sin repetir
     * (si la colonia se llama igual que la ciudad, se busca una sola vez).
     *
     * @return array<string, string> nivel => texto
     */
    public static function queries(array $property): array
    {
        $location = self::clean($property['location'] ?? '');
        $city     = self::clean($property['city'] ?? '');
        $state    = self::clean($property['state'] ?? '');

        $candidates = [
            'location' => $location !== '' ? [$location, $city, $state] : [],
            'city'     => $city !== '' ? [$city, $state] : [],
            'state'    => $state !== '' ? [$state] : [],
        ];

        $queries = [];
        foreach ($candidates as $level => $parts) {
            $parts = array_values(array_unique(array_filter($parts, static fn ($p) => $p !== '')));
            if ($parts === []) {
                continue;
            }
            $query = implode(', ', $parts) . ', México';
            if (! in_array($query, $queries, true)) {
                $queries[$level] = $query;
            }
        }

        return $queries;
    }

    /** La dirección para mostrar y para Google Maps / Waze: "Popotla, Miguel Hidalgo, CDMX". */
    public static function address(array $property): string
    {
        $parts = array_map(self::clean(...), [$property['location'] ?? '', $property['city'] ?? '', $property['state'] ?? '']);

        return implode(', ', array_unique(array_filter($parts, static fn ($p) => $p !== '')));
    }

    private static function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * @return array{lat: float, lng: float, bbox: list<float>|null}|false|null
     *         null = no se encontró; false = Nominatim no respondió
     */
    private function search(string $query): array|false|null
    {
        if (cache('geo_down') !== null) {
            return false;
        }

        $key    = 'geo_' . md5(mb_strtolower($query));
        $cached = cache($key);

        if (is_array($cached)) {
            return $cached['found'] ? $cached['place'] : null;
        }

        $place = $this->request($query);

        if ($place === false) {
            // Un error de red no se guarda como "no encontrado"; se reintenta en unos minutos
            cache()->save('geo_down', 1, self::TTL_DOWN);

            return false;
        }

        cache()->save($key, ['found' => $place !== null, 'place' => $place], $place !== null ? self::TTL_FOUND : self::TTL_MISS);

        return $place;
    }

    /**
     * @return array{lat: float, lng: float, bbox: list<float>|null}|false|null
     *         null = no se encontró; false = no se pudo consultar
     */
    private function request(string $query): array|false|null
    {
        try {
            $response = Services::curlrequest(['timeout' => self::TIMEOUT, 'connect_timeout' => 3], null, null, false)
                ->get(self::ENDPOINT, [
                    'headers'     => ['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'],
                    'query'       => [
                        'q'               => $query,
                        'format'          => 'jsonv2',
                        'limit'           => 1,
                        'countrycodes'    => 'mx',
                        'accept-language' => 'es',
                    ],
                    'http_errors' => false,
                ]);
        } catch (Throwable $e) {
            log_message('warning', 'Geocoder: {message}', ['message' => $e->getMessage()]);

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            log_message('warning', 'Geocoder: HTTP {code} para "{query}"', ['code' => $response->getStatusCode(), 'query' => $query]);

            return false;
        }

        return self::parse((string) $response->getBody());
    }

    /**
     * Interpreta la respuesta de Nominatim.
     *
     * @return array{lat: float, lng: float, bbox: list<float>|null}|false|null
     */
    public static function parse(string $body): array|false|null
    {
        $data = json_decode($body, true);

        if (! is_array($data)) {
            return false;
        }

        $first = $data[0] ?? null;
        if (! is_array($first) || ! is_numeric($first['lat'] ?? null) || ! is_numeric($first['lon'] ?? null)) {
            return null;
        }

        $bbox = null;
        if (is_array($first['boundingbox'] ?? null) && count($first['boundingbox']) === 4
            && count(array_filter($first['boundingbox'], 'is_numeric')) === 4) {
            $bbox = array_map('floatval', array_values($first['boundingbox']));
        }

        return [
            'lat'  => round((float) $first['lat'], 6),
            'lng'  => round((float) $first['lon'], 6),
            'bbox' => $bbox,
        ];
    }
}
