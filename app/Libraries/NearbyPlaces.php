<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * "Lo que hay cerca" de una propiedad: escuelas, parques, cultura, salud,
 * compras, transporte y restaurantes a menos de RADIUS metros, con los
 * datos de OpenStreetMap (Overpass API), y un texto automático de
 * "¿Por qué vivir aquí?" armado SOLO con esos datos.
 *
 * Las distancias se miden desde el centro de la zona que encontró
 * Geocoder (las propiedades no guardan coordenadas): son aproximadas.
 *
 * Resultados en caché 30 días por zona; si Overpass no responde se deja
 * de intentar por 10 minutos (igual que Geocoder).
 * Política de uso: https://wiki.openstreetmap.org/wiki/Overpass_API#Public_Overpass_API_instances
 *
 * Usan esto: Property::nearby (JSON), partials/nearby.php, public/js/nearby.js
 */
class NearbyPlaces
{
    public const RADIUS = 1000; // metros (~15 min caminando)

    /** Metros por minuto caminando (4.8 km/h). */
    private const WALK = 80;

    /**
     * Servidores públicos de Overpass, en orden. Si uno falla (saturado,
     * límite de consultas: en Hostinger muchos sitios comparten la misma IP),
     * se prueba el siguiente.
     */
    private const ENDPOINTS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
    ];
    private const USER_AGENT = 'InmobiliariaVMCH/1.0 (+https://inmobiliaria.vmch.net)';
    private const TIMEOUT    = 15;  // por servidor
    private const MAX_TIME   = 24;  // en total (PHP suele cortar a los 30 s)
    private const TTL_FOUND  = 30 * DAY;
    private const TTL_EMPTY  = DAY;
    private const TTL_DOWN   = 10 * MINUTE;
    private const MAX_PLACES = 80;

    /**
     * Categorías en el orden en que se muestran.
     * Los colores también los usa el mapa (public/js/location.js).
     */
    public const CATEGORIES = [
        'school'    => ['label' => 'Escuelas',     'icon' => 'school',            'color' => '#2F6FDE'],
        'park'      => ['label' => 'Parques',      'icon' => 'park',              'color' => '#2E9E5B'],
        'culture'   => ['label' => 'Cultura',      'icon' => 'museum',            'color' => '#8E5BD0'],
        'health'    => ['label' => 'Salud',        'icon' => 'local_hospital',    'color' => '#D9534F'],
        'shop'      => ['label' => 'Compras',      'icon' => 'shopping_cart',     'color' => '#E08A1E'],
        'transit'   => ['label' => 'Transporte',   'icon' => 'directions_subway', 'color' => '#0F172A'],
        'food'      => ['label' => 'Restaurantes', 'icon' => 'restaurant',        'color' => '#B07D4F'],
    ];

    /**
     * Lugares cerca de un punto, del más cercano al más lejano.
     *
     * @return list<array{name: string, cat: string, type: string, dist: int, min: int, lat: float, lng: float}>|null
     *         null si Overpass no respondió
     */
    public function around(float $lat, float $lng): ?array
    {
        // "v2": ignora lo guardado por la primera versión (podía guardar como vacía una zona en la que Overpass falló)
        $key    = sprintf('nearby2_%s_%s', number_format($lat, 4, '_', ''), number_format($lng, 4, '_', ''));
        $key    = str_replace('-', 'm', $key);
        $cached = cache($key);

        if (is_array($cached)) {
            return $cached;
        }
        if (cache('nearby2_down') !== null) {
            return null;
        }

        $elements = $this->request(self::query($lat, $lng));

        if ($elements === null) {
            cache()->save('nearby2_down', 1, self::TTL_DOWN);

            return null;
        }

        $places = self::parse($elements, $lat, $lng);
        // Una zona sin lugares se vuelve a consultar al día siguiente
        cache()->save($key, $places, $places ? self::TTL_FOUND : self::TTL_EMPTY);

        return $places;
    }

    /** Consulta Overpass QL: lugares con nombre de las categorías de arriba. */
    public static function query(float $lat, float $lng): string
    {
        $around = sprintf('(around:%d,%.6F,%.6F)', self::RADIUS, $lat, $lng);

        return '[out:json][timeout:20];('
            . "nwr{$around}[amenity~\"^(school|kindergarten|college|university|library|theatre|arts_centre|hospital|clinic|doctors|pharmacy|marketplace|restaurant|cafe)$\"][name];"
            . "nwr{$around}[leisure~\"^(park|garden|playground)$\"][name];"
            . "nwr{$around}[tourism~\"^(museum|gallery)$\"][name];"
            . "nwr{$around}[historic~\"^(monument|memorial|archaeological_site|building|church|castle|ruins|tree)$\"][name];"
            . "nwr{$around}[shop~\"^(supermarket|mall|department_store)$\"][name];"
            . "nwr{$around}[railway~\"^(station|halt)$\"][name];"
            . "nwr{$around}[public_transport=station][name];"
            . ');out center tags 300;';
    }

    /**
     * Convierte la respuesta de Overpass en la lista de lugares: clasifica,
     * calcula distancia y minutos a pie, quita repetidos (mismo nombre y
     * categoría: se queda el más cercano) y ordena por distancia.
     *
     * @param list<array<string, mixed>> $elements
     *
     * @return list<array{name: string, cat: string, type: string, dist: int, min: int, lat: float, lng: float}>
     */
    public static function parse(array $elements, float $lat, float $lng): array
    {
        $places = [];

        foreach ($elements as $el) {
            $tags = is_array($el['tags'] ?? null) ? $el['tags'] : [];
            $name = trim((string) ($tags['name'] ?? ''));
            $pLat = $el['lat'] ?? $el['center']['lat'] ?? null;
            $pLng = $el['lon'] ?? $el['center']['lon'] ?? null;

            if ($name === '' || ! is_numeric($pLat) || ! is_numeric($pLng)) {
                continue;
            }

            $class = self::classify($tags);
            if ($class === null) {
                continue;
            }

            $dist = (int) round(self::distance($lat, $lng, (float) $pLat, (float) $pLng));
            if ($dist > self::RADIUS * 1.1) {
                continue; // el centro de un área grande puede quedar fuera
            }

            [$cat, $type] = $class;
            if ($type === 'Metro' && mb_stripos($name, 'metro') === false) {
                $name = 'Metro ' . $name; // en OpenStreetMap la estación se llama solo "Popotla"
            }
            $key = $cat . '|' . mb_strtolower($name);

            if (! isset($places[$key]) || $places[$key]['dist'] > $dist) {
                $places[$key] = [
                    'name' => mb_substr($name, 0, 80),
                    'cat'  => $cat,
                    'type' => $type,
                    'dist' => $dist,
                    'min'  => max(1, (int) ceil($dist / self::WALK)),
                    'lat'  => round((float) $pLat, 6),
                    'lng'  => round((float) $pLng, 6),
                ];
            }
        }

        $places = array_values($places);
        usort($places, static fn ($a, $b) => $a['dist'] <=> $b['dist']);

        return array_slice($places, 0, self::MAX_PLACES);
    }

    /**
     * Categoría y tipo (en español) según las etiquetas de OpenStreetMap.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function classify(array $tags): ?array
    {
        $amenity = $tags['amenity'] ?? '';
        $map     = [
            'school'       => ['school', 'Escuela'],
            'kindergarten' => ['school', 'Preescolar'],
            'college'      => ['school', 'Escuela superior'],
            'university'   => ['school', 'Universidad'],
            'library'      => ['culture', 'Biblioteca'],
            'theatre'      => ['culture', 'Teatro'],
            'arts_centre'  => ['culture', 'Centro cultural'],
            'hospital'     => ['health', 'Hospital'],
            'clinic'       => ['health', 'Clínica'],
            'doctors'      => ['health', 'Consultorio médico'],
            'pharmacy'     => ['health', 'Farmacia'],
            'marketplace'  => ['shop', 'Mercado'],
            'restaurant'   => ['food', 'Restaurante'],
            'cafe'         => ['food', 'Café'],
        ];
        if (isset($map[$amenity])) {
            return $map[$amenity];
        }

        $tourism = $tags['tourism'] ?? '';
        if ($tourism === 'museum') {
            return ['culture', 'Museo'];
        }
        if ($tourism === 'gallery') {
            return ['culture', 'Galería'];
        }

        $shop = $tags['shop'] ?? '';
        $shops = ['supermarket' => 'Supermercado', 'mall' => 'Centro comercial', 'department_store' => 'Tienda departamental'];
        if (isset($shops[$shop])) {
            return ['shop', $shops[$shop]];
        }

        $leisure = $tags['leisure'] ?? '';
        $parks = ['park' => 'Parque', 'garden' => 'Jardín', 'playground' => 'Juegos infantiles'];
        if (isset($parks[$leisure])) {
            return ['park', $parks[$leisure]];
        }

        if (($tags['railway'] ?? '') !== '' || ($tags['public_transport'] ?? '') === 'station') {
            $station = $tags['station'] ?? '';

            return ['transit', match (true) {
                $station === 'subway'                         => 'Metro',
                $station === 'light_rail'                     => 'Tren ligero',
                ($tags['bus'] ?? '') === 'yes' || ($tags['highway'] ?? '') === 'bus_stop' => 'Estación de autobús',
                ($tags['railway'] ?? '') !== ''               => 'Estación de tren',
                default                                       => 'Estación',
            }];
        }

        if (($tags['historic'] ?? '') !== '') {
            return ['culture', 'Sitio histórico'];
        }

        return null;
    }

    /** Distancia en metros entre dos puntos (fórmula del semiverseno). */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** @return array<string, int> cantidad por categoría (todas las categorías, aunque sea 0) */
    public static function counts(array $places): array
    {
        $counts = array_fill_keys(array_keys(self::CATEGORIES), 0);
        foreach ($places as $p) {
            $counts[$p['cat']]++;
        }

        return $counts;
    }

    /**
     * Texto de "¿Por qué vivir aquí?" a partir de los lugares encontrados.
     * Solo afirma lo que dicen los datos (cantidades y distancias).
     *
     * @return array{title: string, text: string, tags: list<array{icon: string, title: string, text: string}>}
     */
    public static function summary(array $places, string $zone): array
    {
        $c       = self::counts($places);
        $nearest = [];
        foreach ($places as $p) {
            $nearest[$p['cat']] ??= $p; // la lista viene ordenada por distancia
        }

        // Ventajas (máximo 4, en orden de importancia)
        $tags = [];
        if ($c['school'] >= 2 && $c['park'] >= 1) {
            $tags['family'] = ['icon' => 'family_restroom', 'title' => 'Ideal para familias',
                'text' => self::count($c['school'], 'escuela', 'escuelas') . ' y ' . self::count($c['park'], 'parque', 'parques') . ' cerca'];
        }
        if (isset($nearest['transit'])) {
            $t = $nearest['transit'];
            $tags['transit'] = ['icon' => 'directions_subway', 'title' => 'Bien comunicada',
                'text' => $t['name'] . ' a ' . self::minutes($t['min']) . ' caminando'];
        }
        if ($c['shop'] + $c['health'] >= 2) {
            $types = array_values(array_unique(array_map(
                static fn ($p) => mb_strtolower($p['type']),
                array_filter($places, static fn ($p) => in_array($p['cat'], ['shop', 'health'], true))
            )));
            $tags['daily'] = ['icon' => 'shopping_bag', 'title' => 'Todo a la mano',
                'text' => self::ucfirst(self::join(array_map([self::class, 'plural'], array_slice($types, 0, 3))))];
        }
        if ($c['culture'] >= 1) {
            $tags['culture'] = ['icon' => 'account_balance', 'title' => 'Cultura cerca',
                'text' => self::count($c['culture'], 'lugar de cultura', 'lugares de cultura') . ' a pie'];
        }
        if (! isset($tags['family']) && $c['park'] >= 2) {
            $tags['green'] = ['icon' => 'park', 'title' => 'Áreas verdes',
                'text' => self::count($c['park'], 'parque o jardín', 'parques y jardines') . ' cerca'];
        }
        if ($c['food'] >= 5) {
            $tags['food'] = ['icon' => 'restaurant', 'title' => 'Para salir a comer',
                'text' => self::count($c['food'], 'restaurante o café', 'restaurantes y cafés')];
        }
        $tags = array_slice($tags, 0, 4, true);

        $title = match (true) {
            count($tags) >= 3 => 'Una zona con todo a la mano',
            isset($tags['family']) => 'Una zona pensada para familias',
            isset($tags['transit']) => 'Una zona bien comunicada',
            default => 'Así es la zona',
        };

        // Párrafo
        $list = [];
        foreach (['school' => ['escuela', 'escuelas'], 'park' => ['parque', 'parques'],
            'shop' => ['lugar para hacer compras', 'lugares para hacer compras'],
            'health' => ['servicio de salud', 'servicios de salud'],
            'culture' => ['lugar de cultura', 'lugares de cultura']] as $cat => [$one, $many]) {
            if ($c[$cat] > 0) {
                $list[] = self::count($c[$cat], $one, $many);
            }
        }

        $where = $zone !== '' ? 'Alrededor de ' . $zone . ', a' : 'A';
        $text  = $list ? $where . ' unos minutos caminando, hay ' . self::join($list) . '.' : '';

        if (isset($nearest['transit'])) {
            $t = $nearest['transit'];
            $text .= ' ' . $t['name'] . ' está a ' . self::minutes($t['min']) . ' a pie, así que moverse sin auto es fácil.';
        }
        if (isset($tags['family'])) {
            $text .= ' Una ubicación cómoda para familias con niños.';
        } elseif (count($tags) >= 2) {
            $text .= ' Una ubicación práctica para el día a día.';
        }

        return ['title' => $title, 'text' => trim($text), 'tags' => array_values($tags)];
    }

    private static function count(int $n, string $one, string $many): string
    {
        return $n . ' ' . ($n === 1 ? $one : $many);
    }

    private static function minutes(int $min): string
    {
        return $min === 1 ? '1 minuto' : $min . ' minutos';
    }

    /** "a, b y c" */
    private static function join(array $items): string
    {
        if (count($items) <= 1) {
            return (string) ($items[0] ?? '');
        }
        $last = array_pop($items);

        return implode(', ', $items) . ' y ' . $last;
    }

    /** Plural sencillo de los tipos de compras y salud ("farmacia" → "farmacias"). */
    private static function plural(string $type): string
    {
        return match ($type) {
            'consultorio médico'   => 'consultorios',
            'centro comercial'     => 'centros comerciales',
            'tienda departamental' => 'tiendas departamentales',
            'hospital'             => 'hospitales',
            default                => $type . 's',
        };
    }

    private static function ucfirst(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    /**
     * Pregunta a los servidores de Overpass hasta que uno responda bien.
     *
     * @return list<array<string, mixed>>|null los elementos, o null si ninguno respondió
     */
    private function request(string $query): ?array
    {
        $start = microtime(true);

        foreach (self::ENDPOINTS as $endpoint) {
            $left = self::MAX_TIME - (microtime(true) - $start);
            if ($left < 5) {
                break;
            }

            try {
                $response = Services::curlrequest([
                    'timeout'         => (int) min(self::TIMEOUT, $left),
                    'connect_timeout' => 5,
                ], null, null, false)->post($endpoint, [
                    'headers'     => ['User-Agent' => self::USER_AGENT],
                    'form_params' => ['data' => $query],
                    'http_errors' => false,
                ]);
            } catch (Throwable $e) {
                log_message('error', 'NearbyPlaces: {endpoint}: {message}', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);

                continue;
            }

            $elements = self::elements((string) $response->getBody());

            if ($response->getStatusCode() === 200 && $elements !== null) {
                return $elements;
            }

            log_message('error', 'NearbyPlaces: {endpoint}: HTTP {code} {body}', [
                'endpoint' => $endpoint,
                'code'     => $response->getStatusCode(),
                'body'     => mb_substr(trim(strip_tags((string) $response->getBody())), 0, 200),
            ]);
        }

        return null;
    }

    /**
     * Los elementos de una respuesta de Overpass, o null si no es válida.
     * Cuando Overpass se queda sin tiempo o memoria responde 200 con un
     * "remark" de error y la lista vacía: eso NO es "no hay lugares".
     *
     * @return list<array<string, mixed>>|null
     */
    public static function elements(string $body): ?array
    {
        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['elements']) || ! is_array($data['elements'])) {
            return null;
        }
        if (isset($data['remark']) && preg_match('/error|timed out|out of memory/i', (string) $data['remark'])) {
            return null;
        }

        return $data['elements'];
    }
}
