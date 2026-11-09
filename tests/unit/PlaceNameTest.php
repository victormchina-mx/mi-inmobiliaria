<?php

use App\Libraries\NearbyPlaces;
use App\Libraries\PlaceName;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Casos reales de "Lo que hay cerca" (OpenStreetMap) en las propiedades del sitio.
 *
 * @internal
 */
final class PlaceNameTest extends CIUnitTestCase
{
    public function testRemovesSchoolCodesQuotesAndShift(): void
    {
        $this->assertSame('Escuela Secundaria José Vasconcelos', str_replace('Jose', 'José', PlaceName::clean('Escuela Secundaria Jose Vasconcelos 15dtv0277k')));
        $this->assertSame('Escuela Primaria Profra Magdalena Reyes Gómez', PlaceName::clean('Escuela Primaria Profra Magdalena Reyes Gómez Cct 15epr4213a'));
        $this->assertSame('Instituto Pedagógico Albert Einstein', PlaceName::clean('Instituto Pedagogico Albert Einstein Cct: 15ppr3315'));
        $this->assertSame('Preescolar Narciso Mendoza', PlaceName::clean('Preescolar Narciso Mendoza Cct 15ejn1220z Matutino'));
        $this->assertSame('Jardín de Niños Narciso Mendoza', PlaceName::clean('Jardín de Niños "Narciso Mendoza"'));
        $this->assertSame('Ambigu', PlaceName::clean('"Ambigu"'));
        $this->assertSame('Consultorio Médico Similares', PlaceName::clean('Consultorio Medico Similares Turno De Domingo'));
        $this->assertSame('Instituto Anita Brenner, A.C.', PlaceName::clean('Instituto Anita Brenner, A.C.'));
    }

    public function testFixesCapitalsAndAccents(): void
    {
        $this->assertSame('Clínica Oftálmica del Centro', PlaceName::clean('CLINICA OFTALMICA DEL CENTRO'));
        $this->assertSame('Petrona', PlaceName::clean('PETRONA'));
        $this->assertSame('KALI', PlaceName::clean('KALI'));                        // sigla corta: se queda
        $this->assertSame('Casco de Santo Tomás IPN', PlaceName::clean('Casco de Santo Tomás IPN'));
        $this->assertSame('Colegio Preston', PlaceName::clean('colegio Preston'));
        $this->assertSame('Consultorio Médico Sagrado Corazon', PlaceName::clean('Consultorio Medico Sagrado Corazon'));
        $this->assertSame('Guardería Lactantes Y Maternal Mapetite Ecole', PlaceName::clean('Guarderia Lactantes Y Maternal Mapetite Ecole'));
        $this->assertSame('Universidad Autónoma de Aguascalientes', PlaceName::clean('Universidad Autonoma de Aguascalientes'));
    }

    public function testGenericNames(): void
    {
        $this->assertTrue(PlaceName::isGeneric('Cafetería', 'Café'));
        $this->assertTrue(PlaceName::isGeneric('Taquería', 'Restaurante'));
        $this->assertTrue(PlaceName::isGeneric('Consultorio Médico', 'Consultorio médico'));
        $this->assertTrue(PlaceName::isGeneric('Hamburguesas', 'Restaurante'));
        $this->assertFalse(PlaceName::isGeneric('Taquería Nativitas', 'Restaurante'));
        $this->assertFalse(PlaceName::isGeneric('Farmacias del Ahorro', 'Farmacia'));
    }

    public function testReclassify(): void
    {
        $this->assertNull(PlaceName::reclassify('Tae Kwon Do', 'school', 'Escuela'));
        $this->assertNull(PlaceName::reclassify('Escuela De Natacion Vida Aquatik', 'school', 'Escuela'));
        $this->assertNull(PlaceName::reclassify('Mukande Yoga Estudio', 'school', 'Escuela'));
        $this->assertNull(PlaceName::reclassify('Salon de fiestas', 'park', 'Parque'));
        $this->assertNull(PlaceName::reclassify('Optica Abordo', 'health', 'Clínica'));
        $this->assertSame(['health', 'Farmacia'], PlaceName::reclassify('Farmacias Similares', 'health', 'Hospital'));
        $this->assertSame(['health', 'Consultorio médico'], PlaceName::reclassify('Consultorio Medico Farmacia Similares', 'health', 'Consultorio médico'));
        $this->assertSame(['school', 'Escuela'], PlaceName::reclassify('Escuela Primaria Juan Escutia', 'school', 'Escuela'));
    }

    public function testTidyAndDistanceLabels(): void
    {
        $p = static fn (string $name, string $cat, string $type, int $dist) => ['name' => $name, 'cat' => $cat, 'type' => $type, 'dist' => $dist, 'min' => max(1, (int) ceil($dist / 80)), 'lat' => 0.0, 'lng' => 0.0];

        $places = NearbyPlaces::tidy([
            $p('Metro Popotla', 'transit', 'Metro', 0),
            $p('Cafeteria', 'food', 'Café', 120),
            $p('Tae Kwon Do', 'school', 'Escuela', 200),
            $p('CLINICA OFTALMICA DEL CENTRO', 'health', 'Clínica', 300),
            $p('Escuela Secundaria Jose Vasconcelos 15dtv0277k', 'school', 'Escuela', 400),
        ]);

        $this->assertSame(['Metro Popotla', 'Clínica Oftálmica del Centro', 'Escuela Secundaria Jose Vasconcelos'], array_column($places, 'name'));
        $this->assertSame($places, NearbyPlaces::tidy($places)); // aplicarlo dos veces no cambia nada

        $this->assertSame('A unos pasos', NearbyPlaces::distanceLabel(0));
        $this->assertSame('120 m', NearbyPlaces::distanceLabel(120));
        $this->assertSame('1.2 km', NearbyPlaces::distanceLabel(1180));

        $summary = NearbyPlaces::summary($places, 'Popotla');
        $this->assertStringContainsString('Metro Popotla está a unos pasos', $summary['text']);
    }
}
