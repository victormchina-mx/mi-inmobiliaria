<?php

use App\Libraries\PropertyExtras;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Terreno con hasta 2 decimales (PropertyExtras::decimal / area).
 *
 * @internal
 */
final class PropertyExtrasTest extends CIUnitTestCase
{
    public function testLandSizeKeepsTwoDecimals(): void
    {
        $this->assertSame(496.5, PropertyExtras::decimal('496.5', 999_999));
        $this->assertSame(1250.25, PropertyExtras::decimal('1,250.25', 999_999));
        $this->assertSame(10.13, PropertyExtras::decimal('10.129', 999_999));
        $this->assertNull(PropertyExtras::decimal('', 999_999));
        $this->assertNull(PropertyExtras::decimal('abc', 999_999));
        $this->assertSame(0.0, PropertyExtras::decimal('-3', 999_999));
    }

    public function testAreaHidesZeroDecimals(): void
    {
        $this->assertSame('496', PropertyExtras::area('496.00'));
        $this->assertSame('496.5', PropertyExtras::area(496.5));
        $this->assertSame('1,250.25', PropertyExtras::area('1250.25'));
    }
}
