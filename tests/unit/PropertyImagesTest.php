<?php

use App\Libraries\PropertyImages;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Fotos de las propiedades (PropertyImages): qué archivos tiene una
 * propiedad, cuáles se reemplazaron y qué nombres se pueden borrar.
 *
 * @internal
 */
final class PropertyImagesTest extends CIUnitTestCase
{
    public function testFilesOfIgnoresEmptyAndRepeated(): void
    {
        $p = ['pimage' => 'a.jpg', 'pimage1' => '', 'pimage2' => 'b.jpg', 'pimage3' => 'a.jpg', 'mapimage' => null, 'title' => 'x.jpg'];

        $this->assertSame(['a.jpg', 'b.jpg'], PropertyImages::filesOf($p));
        $this->assertSame([], PropertyImages::filesOf(null));
    }

    public function testReplacedListsOnlyFilesNoLongerUsed(): void
    {
        $before = ['pimage' => 'a.jpg', 'pimage1' => 'b.jpg', 'pimage2' => 'c.jpg'];
        $after  = ['pimage' => 'a.jpg', 'pimage1' => 'nueva.jpg', 'pimage2' => 'c.jpg'];

        $this->assertSame(['b.jpg'], PropertyImages::replaced($before, $after));
        $this->assertSame([], PropertyImages::replaced($before, $before));
    }

    public function testOnlySimpleNamesCanBeDeleted(): void
    {
        $this->assertTrue(PropertyImages::safeName('1794183927_46674429fb6438b78d4c.jpg'));
        $this->assertTrue(PropertyImages::safeName('zillhms1.jpg'));
        $this->assertFalse(PropertyImages::safeName('../app/Config/App.php'));
        $this->assertFalse(PropertyImages::safeName('.htaccess'));
        $this->assertFalse(PropertyImages::safeName('carpeta/foto.jpg'));
        $this->assertFalse(PropertyImages::safeName(''));
    }
}
