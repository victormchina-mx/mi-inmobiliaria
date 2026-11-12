<?php

use App\Libraries\Agenda;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Reglas de la agenda de citas (App\Libraries\Agenda).
 *
 * @internal
 */
final class AgendaTest extends CIUnitTestCase
{
    public function testWeekStartsOnMonday(): void
    {
        $this->assertSame('2026-11-09', Agenda::weekStart('2026-11-15')->format('Y-m-d'));
        $this->assertSame('2026-11-09', Agenda::weekStart('2026-11-09')->format('Y-m-d'));
        $this->assertSame('1', Agenda::weekStart('no-es-fecha')->format('N'));
    }

    public function testWeekTitle(): void
    {
        $this->assertSame('9 – 15 de noviembre 2026', Agenda::weekTitle(Agenda::weekStart('2026-11-10')));
        $this->assertSame('30 de noviembre – 6 de diciembre 2026', Agenda::weekTitle(Agenda::weekStart('2026-12-01')));
        $this->assertSame('28 de diciembre 2026 – 3 de enero 2027', Agenda::weekTitle(Agenda::weekStart('2026-12-30')));
    }

    public function testOnlyInvolvedAgentsCanEdit(): void
    {
        $appt = ['created_by' => 1, 'shower_uid' => 2, 'owner_uid' => 3];
        $this->assertTrue(Agenda::canEdit($appt, 1));
        $this->assertTrue(Agenda::canEdit($appt, 2));
        $this->assertTrue(Agenda::canEdit($appt, 3));
        $this->assertFalse(Agenda::canEdit($appt, 4));
        $this->assertFalse(Agenda::canEdit($appt, 0));
    }

    public function testShortName(): void
    {
        $this->assertSame('Ana R.', Agenda::shortName('Ana Ruiz López'));
        $this->assertSame('Ana', Agenda::shortName('Ana'));
        $this->assertSame('Óscar Á.', Agenda::shortName(' Óscar  Álvarez '));
    }

    public function testOverlappingAppointmentsGoSideBySide(): void
    {
        $e = static fn ($s, $f) => ['starts_at' => "2026-11-09 {$s}:00", 'ends_at' => "2026-11-09 {$f}:00"];
        $out = Agenda::lanes([$e('10:00', '11:00'), $e('10:30', '11:30'), $e('12:00', '13:00')]);

        $this->assertSame([0, 1, 0], array_column($out, 'lane'));
        $this->assertSame([2, 2, 1], array_column($out, 'lanes'));
    }

    public function testPositionInsideTheDay(): void
    {
        $pos = Agenda::position('2026-11-09 08:00:00', '2026-11-09 21:00:00');
        $this->assertEquals(['top' => 0, 'height' => 100], $pos);
        $early = Agenda::position('2026-11-09 06:00:00', '2026-11-09 07:00:00');
        $this->assertEquals(0, $early['top']);
        $this->assertGreaterThan(0, $early['height']);
    }

    public function testFromInput(): void
    {
        [$data, $errors] = Agenda::fromInput([
            'pid' => '25', 'date' => '2026-11-10', 'time' => '10:30', 'duration' => '90',
            'shower_uid' => '2', 'client_name' => ' Ana Ruiz ', 'client_phone' => '55 1234 5678', 'client_email' => 'ana@example.com',
            'result' => 'offer', 'next_date' => '',
        ], [1, 2]);

        $this->assertSame([], $errors);
        $this->assertSame('2026-11-10 10:30:00', $data['starts_at']);
        $this->assertSame('2026-11-10 12:00:00', $data['ends_at']);
        $this->assertSame('Ana Ruiz', $data['client_name']);
        $this->assertSame('offer', $data['result']);
        // Ya no se guardan teléfono ni correo del cliente
        $this->assertArrayNotHasKey('client_phone', $data);
        $this->assertArrayNotHasKey('client_email', $data);
        $this->assertNull($data['next_date']);
    }

    public function testFromInputErrors(): void
    {
        [, $errors] = Agenda::fromInput([
            'pid' => '', 'date' => '2026-02-30', 'time' => '25:00', 'shower_uid' => '9',
            'client_name' => '', 'result' => 'hackeado',
        ], [1, 2]);

        $this->assertContains('Elige el inmueble.', $errors);
        $this->assertContains('Elige la fecha de la cita.', $errors);
        $this->assertContains('Elige la hora de la cita.', $errors);
        $this->assertContains('Elige el agente que muestra el inmueble.', $errors);
        $this->assertContains('Escribe el nombre del cliente.', $errors);
    }
}
