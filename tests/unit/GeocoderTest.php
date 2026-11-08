<?php

use App\Libraries\Geocoder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class GeocoderTest extends CIUnitTestCase
{
    public function testQueriesGoFromMostPreciseToMostGeneral(): void
    {
        $queries = Geocoder::queries(['location' => 'Popotla', 'city' => 'Miguel Hidalgo', 'state' => 'CDMX']);

        $this->assertSame([
            'location' => 'Popotla, Miguel Hidalgo, CDMX, México',
            'city'     => 'Miguel Hidalgo, CDMX, México',
            'state'    => 'CDMX, México',
        ], $queries);
    }

    public function testQueriesSkipRepeatedAndEmptyParts(): void
    {
        $queries = Geocoder::queries(['location' => ' Aguascalientes ', 'city' => 'Aguascalientes', 'state' => 'Aguascalientes']);
        $this->assertSame(['location' => 'Aguascalientes, México'], $queries);

        $this->assertSame(['state' => 'Jalisco, México'], Geocoder::queries(['location' => '', 'city' => null, 'state' => 'Jalisco']));
        $this->assertSame([], Geocoder::queries([]));
    }

    public function testAddressJoinsDistinctParts(): void
    {
        $this->assertSame('Toluca, Metepec, México', Geocoder::address(['location' => 'Toluca', 'city' => 'Metepec', 'state' => 'México']));
        $this->assertSame('Aguascalientes', Geocoder::address(['location' => 'Aguascalientes', 'city' => 'Aguascalientes', 'state' => 'Aguascalientes']));
        $this->assertSame('', Geocoder::address([]));
    }

    public function testParseReadsCoordinatesAndBoundingBox(): void
    {
        $body = '[{"lat":"19.4561","lon":"-99.1830","boundingbox":["19.45","19.46","-99.19","-99.17"]}]';

        $this->assertSame(
            ['lat' => 19.4561, 'lng' => -99.183, 'bbox' => [19.45, 19.46, -99.19, -99.17]],
            Geocoder::parse($body)
        );
    }

    public function testParseDistinguishesNotFoundFromBadResponse(): void
    {
        $this->assertNull(Geocoder::parse('[]'));
        $this->assertNull(Geocoder::parse('[{"lat":"x","lon":"1"}]'));
        $this->assertFalse(Geocoder::parse('<html>error</html>'));
        $this->assertSame(['lat' => 1.0, 'lng' => 2.0, 'bbox' => null], Geocoder::parse('[{"lat":"1","lon":"2"}]'));
    }
}
