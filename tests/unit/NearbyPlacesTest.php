<?php

use App\Libraries\NearbyPlaces;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class NearbyPlacesTest extends CIUnitTestCase
{
    private const LAT = 19.4529;
    private const LNG = -99.1755;

    /** Respuesta de Overpass de ejemplo, alrededor de Popotla. */
    private function elements(): array
    {
        return [
            ['type' => 'node', 'lat' => 19.4535, 'lon' => -99.1760, 'tags' => ['amenity' => 'school', 'name' => 'Escuela Primaria Popotla']],
            ['type' => 'way', 'center' => ['lat' => 19.4531, 'lon' => -99.1750], 'tags' => ['leisure' => 'park', 'name' => 'Jardín Popotla']],
            ['type' => 'node', 'lat' => 19.4550, 'lon' => -99.1770, 'tags' => ['railway' => 'station', 'station' => 'subway', 'name' => 'Popotla']],
            ['type' => 'node', 'lat' => 19.4551, 'lon' => -99.1771, 'tags' => ['public_transport' => 'station', 'station' => 'subway', 'name' => 'Popotla']],
            ['type' => 'node', 'lat' => 19.4540, 'lon' => -99.1740, 'tags' => ['historic' => 'tree', 'name' => 'Árbol de la Noche Triste']],
            ['type' => 'node', 'lat' => 19.4500, 'lon' => -99.1780, 'tags' => ['amenity' => 'kindergarten', 'name' => 'Jardín de Niños Sor Juana']],
            ['type' => 'node', 'lat' => 19.4520, 'lon' => -99.1745, 'tags' => ['amenity' => 'pharmacy', 'name' => 'Farmacia del Ahorro']],
            ['type' => 'node', 'lat' => 19.4560, 'lon' => -99.1730, 'tags' => ['amenity' => 'marketplace', 'name' => 'Mercado Popotla']],
            ['type' => 'node', 'lat' => 19.4600, 'lon' => -99.1700, 'tags' => ['shop' => 'convenience', 'name' => 'OXXO']], // no se cuenta
            ['type' => 'node', 'lat' => 19.4530, 'lon' => -99.1756, 'tags' => ['amenity' => 'school']],                       // sin nombre
            ['type' => 'node', 'lat' => 19.6000, 'lon' => -99.1756, 'tags' => ['amenity' => 'school', 'name' => 'Lejana']],    // >1 km
        ];
    }

    public function testParseClassifiesSortsAndDeduplicates(): void
    {
        $places = NearbyPlaces::parse($this->elements(), self::LAT, self::LNG);
        $names  = array_column($places, 'name');

        $this->assertSame('Jardín Popotla', $names[0]); // el más cercano primero
        $this->assertContains('Metro Popotla', $names);   // estación de Metro con prefijo
        $this->assertSame(1, count(array_keys($names, 'Metro Popotla', true))); // sin repetir
        $this->assertNotContains('OXXO', $names);
        $this->assertNotContains('Lejana', $names);
        $this->assertCount(7, $places);

        $distances = array_column($places, 'dist');
        $sorted    = $distances;
        sort($sorted);
        $this->assertSame($sorted, $distances);

        $tree = $places[array_search('Árbol de la Noche Triste', $names, true)];
        $this->assertSame(['culture', 'Sitio histórico'], [$tree['cat'], $tree['type']]);
        $this->assertGreaterThanOrEqual(1, $tree['min']);
    }

    public function testCountsIncludeEveryCategory(): void
    {
        $counts = NearbyPlaces::counts(NearbyPlaces::parse($this->elements(), self::LAT, self::LNG));

        $this->assertSame(['school' => 2, 'park' => 1, 'culture' => 1, 'health' => 1, 'shop' => 1, 'transit' => 1, 'food' => 0], $counts);
    }

    public function testSummaryOnlyStatesWhatTheDataSays(): void
    {
        $places  = NearbyPlaces::parse($this->elements(), self::LAT, self::LNG);
        $summary = NearbyPlaces::summary($places, 'Popotla');
        $titles  = array_column($summary['tags'], 'title');

        $this->assertSame('Una zona con todo a la mano', $summary['title']);
        $this->assertSame(['Ideal para familias', 'Bien comunicada', 'Todo a la mano', 'Cultura cerca'], $titles);
        $this->assertStringStartsWith('Alrededor de Popotla, a unos minutos caminando, hay 2 escuelas, 1 parque,', $summary['text']);
        $this->assertStringContainsString('Metro Popotla está a', $summary['text']);
        $this->assertSame('Farmacias y mercados', $summary['tags'][2]['text']); // por distancia
    }

    public function testSummaryWithFewPlaces(): void
    {
        $places  = NearbyPlaces::parse([$this->elements()[1]], self::LAT, self::LNG);
        $summary = NearbyPlaces::summary($places, '');

        $this->assertSame('Así es la zona', $summary['title']);
        $this->assertSame([], $summary['tags']);
        $this->assertSame('A unos minutos caminando, hay 1 parque.', $summary['text']);
    }

    public function testDistance(): void
    {
        // 0.009° de latitud ≈ 1 km
        $this->assertEqualsWithDelta(1000.8, NearbyPlaces::distance(19.0, -99.0, 19.009, -99.0), 1.0);
    }

    public function testViewboxIsASquareAroundThePoint(): void
    {
        // oeste,norte,este,sur; ±1 km: 0.008983° de latitud, en longitud depende de la latitud
        $this->assertSame('-99.185027,19.461883,-99.165973,19.443917', NearbyPlaces::viewbox(self::LAT, self::LNG));
    }

    public function testFromNominatimKeepsTypeAndExtraTags(): void
    {
        $station = NearbyPlaces::fromNominatim([
            'lat' => '19.4550', 'lon' => '-99.1770', 'name' => 'Popotla',
            'category' => 'railway', 'type' => 'station', 'extratags' => ['station' => 'subway'],
        ]);
        $this->assertSame(['railway' => 'station', 'name' => 'Popotla', 'station' => 'subway'], $station['tags']);

        $places = NearbyPlaces::parse([$station], self::LAT, self::LNG);
        $this->assertSame(['Metro Popotla', 'transit', 'Metro'], [$places[0]['name'], $places[0]['cat'], $places[0]['type']]);

        $this->assertNull(NearbyPlaces::fromNominatim(['lat' => '19.4', 'lon' => '-99.1', 'name' => 'x']));
    }

    public function testLinesAndRoutesAreNotStations(): void
    {
        // Al buscar "subway station" Nominatim también devuelve la línea ("Línea 2")
        $line = NearbyPlaces::fromNominatim(['lat' => '19.4535', 'lon' => '-99.1760', 'name' => 'Línea 2', 'category' => 'railway', 'type' => 'subway']);
        $route = NearbyPlaces::fromNominatim(['lat' => '19.4535', 'lon' => '-99.1760', 'name' => 'Línea 2', 'category' => 'route', 'type' => 'subway']);

        $this->assertSame([], NearbyPlaces::parse([$line, $route], self::LAT, self::LNG));
    }
}
