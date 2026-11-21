<?php

namespace Tests\Unit;

use App\Support\Vin;
use PHPUnit\Framework\TestCase;

class VinTest extends TestCase
{
    public function test_valida_formato(): void
    {
        $this->assertTrue(Vin::formatoValido('1HGCM82633A004352'));
        $this->assertTrue(Vin::formatoValido('1hgcm8263 3a004352'));
        $this->assertFalse(Vin::formatoValido('1HGCM82633A00435'), '16 caracteres');
        $this->assertFalse(Vin::formatoValido('1HGCM82633AO04352'), 'contiene O');
    }

    public function test_calcula_digito_verificador(): void
    {
        $this->assertTrue(Vin::digitoVerificadorValido('1HGCM82633A004352'));
        $this->assertFalse(Vin::digitoVerificadorValido('1HGCM82643A004352'));
    }
}
