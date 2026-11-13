<?php

use App\Libraries\Agency;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Varias inmobiliarias (App\Libraries\Agency): marca, colores y textos.
 * La separación por inmobiliaria se probó contra una base de datos.
 *
 * @internal
 */
final class AgencyTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Agency::reset();
        parent::tearDown();
    }

    public function testColors(): void
    {
        $this->assertSame('#0A2342', Agency::color('#0a2342'));
        $this->assertSame('#0A2342', Agency::color('0A2342'));
        $this->assertNull(Agency::color('red'));
        $this->assertNull(Agency::color('#12345'));
        $this->assertSame('#FFFFFF', Agency::mix('#000000', '#FFFFFF', 1));
        $this->assertSame('#808080', Agency::mix('#000000', '#FFFFFF', .5));
    }

    public function testPaletteUsesTheAgencyColors(): void
    {
        $vars = Agency::palette('#0F3D2E', '#E8C27A');

        $this->assertSame('#0F3D2E', $vars['--fx-navy']);
        $this->assertSame('#E8C27A', $vars['--fx-gold']);
        $this->assertStringContainsString('#E8C27A 45%', $vars['--fx-gold-grad']);
        $this->assertStringContainsString('--fx-navy: #0F3D2E;', Agency::cssVars(['primary' => '#0F3D2E', 'accent' => '#E8C27A']));
    }

    public function testNames(): void
    {
        $this->assertSame('Frices', Agency::shortName('Grupo Inmobiliario Frices'));
        $this->assertSame('Altavista Bienes Raíces', Agency::shortName('Altavista Bienes Raíces'));
        $this->assertSame('CN', Agency::initials('Casa Norte'));
        $this->assertSame('F', Agency::initials('Grupo Inmobiliario Frices'));
        $this->assertSame('altavista-bienes-raices', Agency::slugify('Altavista Bienes Raíces'));
    }

    public function testBrandBeforeTheSqlKeepsFrices(): void
    {
        Agency::reset(false);
        $b = Agency::brand(null);

        $this->assertTrue($b['frices']);
        $this->assertSame(Agency::FRICES, $b['colors']);
    }

    public function testIndependentAgentsUseRealEstateByVick(): void
    {
        Agency::reset(true);
        $b = Agency::brand(null);

        $this->assertTrue($b['independent']);
        $this->assertSame('Real Estate by Vick', $b['name']);
        $this->assertSame(Agency::NEUTRAL, $b['colors']);
    }

    public function testAgencyBrand(): void
    {
        Agency::reset(true);
        $frices = Agency::brand(['slug' => 'frices', 'name' => 'Grupo Inmobiliario Frices', 'logo' => null, 'color_primary' => '#0A2342', 'color_accent' => '#D1A84B']);
        $this->assertTrue($frices['frices']);

        $other = Agency::brand(['slug' => 'altavista', 'name' => 'Altavista', 'logo' => 'logo.png', 'color_primary' => '#0f3d2e', 'color_accent' => 'mal', 'tagline' => 'Tu hogar']);
        $this->assertFalse($other['frices']);
        $this->assertFalse($other['independent']);
        $this->assertStringEndsWith('uploads/agency/logo.png', (string) $other['logo']);
        $this->assertSame(['primary' => '#0F3D2E', 'accent' => Agency::FRICES['accent']], $other['colors']);
    }

    public function testCircleCropOnlyAcceptsRealPngImages(): void
    {
        $this->assertSame('1794_ab12-icon.png', Agency::iconFile('1794_ab12.jpg'));
        $this->assertFalse(Agency::saveIcon('x.png', 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4='));
        $this->assertFalse(Agency::saveIcon('x.png', 'data:image/png;base64,' . base64_encode('<?php echo 1;')));
        $this->assertFalse(Agency::saveIcon('x.png', 'no es una imagen'));
    }
}
