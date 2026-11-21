<?php

namespace Tests\Unit;

use App\Support\Rfc;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RfcTest extends TestCase
{
    public static function validos(): array
    {
        return [
            'persona moral' => ['ABC010203AB1'],
            'persona física' => ['GOMC800115H52'],
            'con ampersand' => ['A&B991231XY9'],
            'genérico nacional' => ['XAXX010101000'],
            'minúsculas y espacios' => [' gomc800115h52 '],
        ];
    }

    public static function invalidos(): array
    {
        return [
            'corto' => ['ABC0102'],
            'fecha imposible' => ['GOMC801315H52'],
            'caracteres raros' => ['GOMC8001#5H52'],
            'vacío' => [''],
        ];
    }

    #[DataProvider('validos')]
    public function test_acepta_rfc_validos(string $rfc): void
    {
        $this->assertTrue(Rfc::esValido($rfc));
    }

    #[DataProvider('invalidos')]
    public function test_rechaza_rfc_invalidos(string $rfc): void
    {
        $this->assertFalse(Rfc::esValido($rfc));
    }

    public function test_distingue_persona_moral(): void
    {
        $this->assertTrue(Rfc::esPersonaMoral('ABC010203AB1'));
        $this->assertFalse(Rfc::esPersonaMoral('GOMC800115H52'));
    }
}
