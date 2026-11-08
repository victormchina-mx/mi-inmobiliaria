<?php

use App\Libraries\NearbyPlaces;
use App\Libraries\PropertyLocation;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PropertyLocationTest extends CIUnitTestCase
{
    public function testParseValidatesCoordinates(): void
    {
        $this->assertSame(['lat' => 19.452861, 'lng' => -99.175462], PropertyLocation::parse('19.4528612', '-99.1754621'));
        $this->assertNull(PropertyLocation::parse('', ''));
        $this->assertNull(PropertyLocation::parse('abc', '-99.1'));
        $this->assertNull(PropertyLocation::parse('95', '-99.1'));   // fuera de rango
        $this->assertNull(PropertyLocation::parse('0', '0'));        // punto vacío de algunos formularios
    }

    public function testPointNeedsBothCoordinates(): void
    {
        $this->assertNull(PropertyLocation::point(['lat' => null, 'lng' => null]));
        $this->assertNull(PropertyLocation::public(['pid' => 1]));
        $this->assertSame(
            ['lat' => 19.45, 'lng' => -99.17, 'exact' => true],
            PropertyLocation::point(['lat' => '19.450000', 'lng' => '-99.170000', 'exact_location' => '1'])
        );
    }

    public function testExactPointIsShownAsIs(): void
    {
        $public = PropertyLocation::public(['pid' => 7, 'lat' => '19.4529', 'lng' => '-99.1755', 'exact_location' => 1]);

        $this->assertSame(['lat' => 19.4529, 'lng' => -99.1755, 'level' => 'exact', 'bbox' => null], $public);
    }

    public function testApproximateZoneIsShiftedAndStable(): void
    {
        $property = ['pid' => 7, 'lat' => '19.4529', 'lng' => '-99.1755', 'exact_location' => 0];
        $public   = PropertyLocation::public($property);
        $shift    = NearbyPlaces::distance(19.4529, -99.1755, $public['lat'], $public['lng']);

        $this->assertSame('approx', $public['level']);
        $this->assertGreaterThanOrEqual(99, $shift);   // la zona no se centra en la casa
        $this->assertLessThanOrEqual(201, $shift);     // pero la casa queda dentro del círculo de 300 m
        $this->assertSame($public, PropertyLocation::public($property)); // igual en cada visita

        $other = PropertyLocation::public(['pid' => 8] + $property);
        $this->assertNotSame([$public['lat'], $public['lng']], [$other['lat'], $other['lng']]);
    }
}
