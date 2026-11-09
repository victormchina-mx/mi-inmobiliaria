<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * "Lo que hay cerca" de una propiedad: escuelas, parques, cultura, salud,
 * compras, transporte y restaurantes a menos de RADIUS metros, con los
 * datos de OpenStreetMap (buscador Nominatim), y un texto automático de
 * "¿Por qué vivir aquí?" armado SOLO con esos datos.
 *
 * Las distancias se miden desde el centro de la zona que encontró
 * Geocoder (las propiedades no guardan coordenadas): son aproximadas.
 *
 * Se busca cada tipo de lugar ("school", "park"...) dentro del cuadrado de
 * ±RADIUS alrededor del punto. Nominatim permite UNA consulta por segundo,
 * así que la primera visita a una zona tarda ~18 s (el detalle muestra
 * "cargando"); el resultado se guarda 30 días por zona. Si Nominatim no
 * responde se deja de intentar por 10 minutos.
 * Política de uso: https://operations.osmfoundation.org/policies/nominatim/
 *
 * Antes se usaba Overpass API, pero desde Hostinger (IP compartida con
 * muchos sitios) sus servidores públicos dejaban las consultas en espera
 * y nunca respondían.
 *
 * Usan esto: Property::nearby (JSON), partials/nearby.php, public/js/nearby.js
 */
class NearbyPlaces
{
    public const RADIUS = 1000; // metros (~15 min caminando)

    /** Metros por minuto caminando (4.8 km/h). */
    private const WALK = 80;

    private const ENDPOINT   = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'InmobiliariaVMCH/1.0 (+https://inmobiliaria.vmch.net)';
    private const TIMEOUT    = 6;    // por consulta
    private const MAX_TIME   = 24;   // en total (PHP suele cortar a los 30 s)
    private const PAUSE      = 1.1;  // segundos entre consultas (máximo 1 por segundo)
    private const TTL_FOUND  = 30 * DAY;
    private const TTL_RETRY  = DAY;  // zona vacía o búsqueda incompleta: se repite mañana
    private const TTL_DOWN   = 10 * MINUTE;

    /**
     * Tipos de lugar que se buscan, en orden de importancia (si se acaba el
     * tiempo, faltan los últimos). Nominatim entiende estas palabras como
     * "lugares de ese tipo"; cada resultado trae su tipo real y classify()
     * descarta lo que no corresponda (p. ej. "Línea 2" al buscar estaciones).
     */
    private const PHRASES = [
        'school', 'kindergarten', 'university', 'park', 'station', 'supermarket', 'marketplace',
        'pharmacy', 'hospital', 'clinic', 'museum', 'library', 'mall', 'playground', 'theatre',
        'restaurant', 'cafe',
    ];
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
     *         null si Nominatim no respondió (o hay otra búsqueda en curso)
     */
    public function around(float $lat, float $lng): ?array
    {
        // "v3": ignora lo guardado por las versiones con Overpass
        $key    = str_replace('-', 'm', sprintf('nearby3_%s_%s', number_format($lat, 4, '_', ''), number_format($lng, 4, '_', '')));
        $cached = cache($key);

        if (is_array($cached)) {
            return self::tidy($cached);
        }

        // Una búsqueda a la vez: dos al mismo tiempo pasarían de 1 consulta por segundo
        if (cache('nearby3_down') !== null || cache('nearby3_busy') !== null) {
            return null;
        }
        cache()->save('nearby3_busy', 1, 40);

        [$elements, $complete, $answered] = $this->collect($lat, $lng);
        cache()->delete('nearby3_busy');

        if (! $answered) {
            cache()->save('nearby3_down', 1, self::TTL_DOWN);

            return null;
        }

        $places = self::parse($elements, $lat, $lng);
        cache()->save($key, $places, $places && $complete ? self::TTL_FOUND : self::TTL_RETRY);

        return self::tidy($places);
    }

    /** Cuadrado de ±RADIUS alrededor del punto, como lo pide Nominatim: "oeste,norte,este,sur". */
    public static function viewbox(float $lat, float $lng): string
    {
        $dLat = self::RADIUS / 111320;
        $dLng = self::RADIUS / (111320 * max(0.2, cos(deg2rad($lat))));

        return sprintf('%.6F,%.6F,%.6F,%.6F', $lng - $dLng, $lat + $dLat, $lng + $dLng, $lat - $dLat);
    }

    /**
     * Un resultado de Nominatim (format=jsonv2, extratags=1) con la misma
     * forma que usa parse(): ['lat', 'lon', 'tags' => [categoría => tipo, name, ...]].
     */
    public static function fromNominatim(array $result): ?array
    {
        $category = (string) ($result['category'] ?? '');
        $type     = (string) ($result['type'] ?? '');

        if ($category === '' || $type === '' || ! is_numeric($result['lat'] ?? null) || ! is_numeric($result['lon'] ?? null)) {
            return null;
        }

        $extra = is_array($result['extratags'] ?? null) ? $result['extratags'] : [];

        return [
            'lat'  => (float) $result['lat'],
            'lon'  => (float) $result['lon'],
            'tags' => [$category => $type, 'name' => (string) ($result['name'] ?? '')] + $extra,
        ];
    }

    /**
     * Busca cada tipo de lugar en Nominatim, respetando 1 consulta por segundo.
     *
     * @return array{0: list<array<string, mixed>>, 1: bool, 2: bool}
     *         [elementos, si se completaron todas las búsquedas, si respondió al menos una]
     */
    private function collect(float $lat, float $lng): array
    {
        $viewbox  = self::viewbox($lat, $lng);
        $start    = microtime(true);
        $last     = 0.0;
        $elements = [];
        $complete = true;
        $answered = false;

        foreach (self::PHRASES as $phrase) {
            if (microtime(true) - $start > self::MAX_TIME - self::TIMEOUT) {
                $complete = false;
                break;
            }

            $wait = self::PAUSE - (microtime(true) - $last);
            if ($last > 0 && $wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
            $last = microtime(true);

            try {
                $response = Services::curlrequest(['timeout' => self::TIMEOUT, 'connect_timeout' => 4], null, null, false)
                    ->get(self::ENDPOINT, [
                        'headers'     => ['User-Agent' => self::USER_AGENT],
                        'query'       => [
                            'q'               => $phrase,
                            'viewbox'         => $viewbox,
                            'bounded'         => 1,
                            'limit'           => 40,
                            'format'          => 'jsonv2',
                            'extratags'       => 1,
                            'accept-language' => 'es',
                        ],
                        'http_errors' => false,
                    ]);
            } catch (Throwable $e) {
                log_message('error', 'NearbyPlaces: "{phrase}": {message}', ['phrase' => $phrase, 'message' => $e->getMessage()]);
                $complete = false;

                continue;
            }

            $status = $response->getStatusCode();
            $data   = json_decode((string) $response->getBody(), true);

            if ($status !== 200 || ! is_array($data)) {
                log_message('error', 'NearbyPlaces: "{phrase}": HTTP {code}', ['phrase' => $phrase, 'code' => $status]);
                $complete = false;
                if (in_array($status, [403, 429], true)) {
                    break; // Nominatim pide parar
                }

                continue;
            }

            $answered = true;
            foreach ($data as $result) {
                if (is_array($result) && ($element = self::fromNominatim($result)) !== null) {
                    $elements[] = $element;
                }
            }
        }

        return [$elements, $complete, $answered];
    }

    /**
     * Convierte los resultados en la lista de lugares: clasifica,
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

        // Solo estaciones: no las vías ni las líneas ("Línea 2")
        if (in_array($tags['railway'] ?? '', ['station', 'halt'], true) || ($tags['public_transport'] ?? '') === 'station') {
            $station = $tags['station'] ?? '';

            return ['transit', match (true) {
                $station === 'subway'                         => 'Metro',
                $station === 'light_rail'                     => 'Tren ligero',
                ($tags['bus'] ?? '') === 'yes' || ($tags['highway'] ?? '') === 'bus_stop' => 'Estación de autobús',
                ($tags['train'] ?? '') === 'yes'              => 'Estación de tren',
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

    /** Distancia que se considera "a unos pasos" (en metros). */
    public const STEPS = 50;

    /**
     * Nombres presentables (App\Libraries\PlaceName): sin claves CCT,
     * comillas ni MAYÚSCULAS, con acentos; sin nombres genéricos
     * ("Cafetería") ni lugares mal clasificados (clases de taekwondo como
     * escuela). Se aplica al mostrar, así corrige también lo guardado.
     *
     * @param list<array{name: string, cat: string, type: string, dist: int, min: int, lat: float, lng: float}> $places
     *
     * @return list<array{name: string, cat: string, type: string, dist: int, min: int, lat: float, lng: float}>
     */
    public static function tidy(array $places): array
    {
        $tidy = [];

        foreach ($places as $p) {
            $class = PlaceName::reclassify((string) $p['name'], $p['cat'], $p['type']);
            if ($class === null) {
                continue;
            }
            [$p['cat'], $p['type']] = $class;

            $p['name'] = PlaceName::clean((string) $p['name']);
            if (PlaceName::isGeneric($p['name'], $p['type'])) {
                continue;
            }

            // Repetidos tras limpiar ("Farmacias Guadalajara" / "Farmacia Guadalajara" no; mismo nombre sí)
            $key = $p['cat'] . '|' . PlaceName::key($p['name']);
            if (! isset($tidy[$key]) || $tidy[$key]['dist'] > $p['dist']) {
                $tidy[$key] = $p;
            }
        }

        $tidy = array_values($tidy);
        usort($tidy, static fn ($a, $b) => $a['dist'] <=> $b['dist']);

        return $tidy;
    }

    /** "A unos pasos" (menos de STEPS m), "350 m" o "1.2 km". */
    public static function distanceLabel(int $meters): string
    {
        return match (true) {
            $meters < self::STEPS => 'A unos pasos',
            $meters >= 1000       => number_format($meters / 1000, 1) . ' km',
            default               => $meters . ' m',
        };
    }

    /** "a unos pasos" o "a 3 minutos" (para frases). */
    public static function walkPhrase(array $place): string
    {
        return $place['dist'] < self::STEPS ? 'a unos pasos' : 'a ' . self::minutes($place['min']);
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
                'text' => $t['name'] . ' ' . ($t['dist'] < self::STEPS ? 'a unos pasos' : 'a ' . self::minutes($t['min']) . ' caminando')];
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
            $text .= ' ' . $t['name'] . ' está ' . ($t['dist'] < self::STEPS ? 'a unos pasos' : 'a ' . self::minutes($t['min']) . ' a pie') . ', así que moverse sin auto es fácil.';
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
}
