<?php

namespace Tests\Unit;

use App\Models\Diagnostico;
use App\Support\DiagramaDanos;
use PHPUnit\Framework\TestCase;

class DiagnosticoDtcTest extends TestCase
{
    public function test_normaliza_codigos_dtc(): void
    {
        $this->assertSame('P0301, P0420, C1234', Diagnostico::normalizarDtc('p0301 p0420,c1234 P0301'));
        $this->assertNull(Diagnostico::normalizarDtc('sin códigos'));
        $this->assertNull(Diagnostico::normalizarDtc(null));
    }

    public function test_el_diagrama_marca_las_zonas_danadas(): void
    {
        $svg = DiagramaDanos::svg(['cofre', 'cofre', 'puerta_del_izq', 'interior']);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertSame(2, substr_count($svg, 'fill="#e5484d"'));
        $this->assertStringContainsString('>2</text>', $svg, 'El cofre tiene dos daños');
    }
}
