<?php

use App\Libraries\FichaTecnica;
use App\Libraries\PropertyExtras;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FichaTecnicaTest extends CIUnitTestCase
{
    private function property(array $extra = []): array
    {
        return $extra + [
            'pid' => 25, 'title' => 'Zills Home', 'type' => 'house', 'stype' => 'sale', 'price' => 219690,
            'location' => 'Mar De Kara', 'city' => 'Azcapotzalco', 'state' => 'CDMX',
            'size' => 1869, 'bedroom' => 4, 'bathroom' => 2, 'land_size' => 2100, 'parking' => 2, 'age_years' => 8,
            'pimage' => 'zillhms1.jpg', 'pimage1' => 'zillhms2.jpg', 'pimage2' => '', 'pimage3' => '', 'pimage4' => '',
        ];
    }

    private function agent(): array
    {
        return ['uname' => 'Thomas Olson', 'uphone' => '7896665555', 'uimage' => 'gr7.png', 'utype' => 'agent'];
    }

    public function testCode(): void
    {
        $this->assertSame('VMCH-0025', FichaTecnica::code(25));
        $this->assertSame('VMCH-12345', FichaTecnica::code(12345));
    }

    public function testDataBuildsTitleZonePriceAndContact(): void
    {
        $f = FichaTecnica::data($this->property(), $this->agent());

        $this->assertSame('Casa en Venta en Mar De Kara', $f['title']);
        $this->assertSame('Mar De Kara · Azcapotzalco, CDMX', $f['place']);
        $this->assertSame('$219,690', $f['price']);
        $this->assertSame('MXN', $f['priceNote']);
        $this->assertCount(2, $f['photos']);
        $this->assertStringEndsWith('/p/25', $f['url']);
        $this->assertSame('789 666 5555', $f['agent']['phone']);
        $this->assertStringStartsWith('https://wa.me/527896665555?text=', $f['agent']['wa']);
        $this->assertStringContainsString(rawurlencode('VMCH-0025'), $f['agent']['wa']);
    }

    public function testRentShowsPerMonth(): void
    {
        $f = FichaTecnica::data($this->property(['stype' => 'rent', 'price' => 18500]), $this->agent());

        $this->assertSame('Casa en Renta en Mar De Kara', $f['title']);
        $this->assertSame('/mes', $f['priceNote']);
    }

    public function testFactsOnlyWithValues(): void
    {
        $labels = array_column(FichaTecnica::facts($this->property()), 'label');
        $this->assertSame(['Construcción', 'Terreno', 'Recámaras', 'Baños', 'Estacionamientos', 'Antigüedad'], $labels);

        $few = FichaTecnica::facts($this->property(['land_size' => null, 'parking' => 0, 'age_years' => 0, 'bathroom' => 1]));
        $this->assertSame(['Construcción', 'Recámaras', 'Baño', 'A estrenar'], array_column($few, 'label'));
    }

    public function testExtrasNumber(): void
    {
        $this->assertNull(PropertyExtras::number('', 99));
        $this->assertNull(PropertyExtras::number('abc', 99));
        $this->assertSame(0, PropertyExtras::number('-3', 99));
        $this->assertSame(99, PropertyExtras::number('150', 99));
        $this->assertSame(3, PropertyExtras::number('2.6', 99));
        $this->assertSame('Caja de seguridad', PropertyExtras::keysLabel('lockbox'));
        $this->assertSame('Sin dato', PropertyExtras::keysLabel(null));
    }
}
