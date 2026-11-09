<?php

use App\Libraries\NearbyPlaces;
use App\Libraries\WhyText;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class WhyTextTest extends CIUnitTestCase
{
    public function testCleanKeepsPlainTextAndLineBreaks(): void
    {
        $this->assertSame("Calle tranquila.\n\nA dos cuadras del parque.", WhyText::clean("  Calle   tranquila. \r\n\r\n\r\n\r\n A dos cuadras del <b>parque</b>.  "));
        $this->assertSame('alert(1) Hola &', WhyText::clean('<script>alert(1)</script> Hola &amp;'));
        $this->assertSame('', WhyText::clean(null));
        $this->assertSame('', WhyText::clean(['x']));
        $this->assertSame(WhyText::MAX_LENGTH, mb_strlen(WhyText::clean(str_repeat('á', 900))));
    }

    public function testOfReadsTheProperty(): void
    {
        $this->assertSame('', WhyText::of([]));
        $this->assertSame('Muy céntrico.', WhyText::of(['why_text' => ' Muy céntrico. ']));
    }

    private function render(array $places, string $custom): string
    {
        return view('partials/nearby', [
            'places'  => $places,
            'counts'  => NearbyPlaces::counts($places),
            'summary' => NearbyPlaces::summary($places, 'Popotla'),
            'custom'  => $custom,
            'mapUrl'  => 'https://example.test/property/1/ubicacion',
            'level'   => 'location',
            'zone'    => 'Popotla',
        ]);
    }

    public function testCustomTextReplacesTheAutomaticParagraph(): void
    {
        $places = NearbyPlaces::parse([
            ['lat' => 19.4535, 'lon' => -99.1760, 'tags' => ['amenity' => 'school', 'name' => 'Primaria Popotla']],
            ['lat' => 19.4536, 'lon' => -99.1761, 'tags' => ['amenity' => 'school', 'name' => 'Secundaria 121']],
            ['lat' => 19.4531, 'lon' => -99.1750, 'tags' => ['leisure' => 'park', 'name' => 'Jardín Popotla']],
        ], 19.4529, -99.1755);

        $html = $this->render($places, "Calle tranquila <b>y arbolada</b>.\nCerca del Metro.");

        $this->assertStringContainsString('Lo que hay cerca', $html);
        $this->assertStringContainsString('Calle tranquila &lt;b&gt;y arbolada&lt;/b&gt;.<br>', $html); // escapado
        $this->assertStringContainsString('Descripción del anunciante.', $html);
        $this->assertStringContainsString('Ideal para familias', $html);                   // las ventajas siguen
        $this->assertStringNotContainsString('a unos minutos caminando, hay', $html);      // sin el párrafo automático
    }

    public function testWithoutPlacesOnlyTheCustomTextIsShown(): void
    {
        $html = $this->render([], 'Zona residencial, muy segura.');

        $this->assertStringNotContainsString('Lo que hay cerca', $html);
        $this->assertStringContainsString('nb-why--solo', $html);
        $this->assertStringContainsString('Vivir en Popotla', $html);
        $this->assertStringContainsString('Zona residencial, muy segura.', $html);
    }

    public function testWithoutCustomTextTheAutomaticOneIsUsed(): void
    {
        $places = NearbyPlaces::parse([
            ['lat' => 19.4531, 'lon' => -99.1750, 'tags' => ['leisure' => 'park', 'name' => 'Jardín Popotla']],
        ], 19.4529, -99.1755);

        $html = $this->render($places, '');

        $this->assertStringContainsString('Alrededor de Popotla, a unos minutos caminando, hay 1 parque.', $html);
        $this->assertStringNotContainsString('Descripción del anunciante', $html);
    }
}
