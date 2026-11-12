<?php

use App\Libraries\AgendaService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Cita para la app móvil (AgendaService::toJson): las notas, comentarios y
 * próxima acción solo van a quien puede editarla.
 *
 * @internal
 */
final class AgendaServiceTest extends CIUnitTestCase
{
    private array $appt = [
        'apid' => 7, 'pid' => 25, 'title' => 'Zills home', 'location' => 'Mar de Kara', 'city' => 'Azcapotzalco',
        'starts_at' => '2026-11-09 10:00:00', 'ends_at' => '2026-11-09 11:00:00',
        'shower_uid' => 2, 'shower_name' => 'Thomas Olson', 'owner_uid' => 3, 'owner_name' => 'Christine Ruiz',
        'created_by' => 2, 'client_name' => 'Ana Ruiz López', 'client_notes' => 'Crédito Infonavit',
        'result' => 'offer', 'comments' => 'Le gustó', 'next_action' => 'Llamar', 'next_date' => '2026-11-12',
    ];

    public function testInvolvedAgentSeesEverything(): void
    {
        $json = AgendaService::toJson($this->appt, 3);

        $this->assertTrue($json['editable']);
        $this->assertSame('VMCH-0025', $json['code']);
        $this->assertSame('Ana Ruiz López', $json['client_name']);
        $this->assertSame('Crédito Infonavit', $json['client_notes']);
        $this->assertSame('Le gustó', $json['comments']);
        $this->assertSame('Oferta', $json['result_label']);
        $this->assertSame('Mar de Kara, Azcapotzalco', $json['zone']);
    }

    public function testOtherAgentsSeeOnlyTheBasics(): void
    {
        $json = AgendaService::toJson($this->appt, 9);

        $this->assertFalse($json['editable']);
        $this->assertSame('Ana R.', $json['client_name']);
        $this->assertNull($json['client_notes']);
        $this->assertNull($json['comments']);
        $this->assertNull($json['next_action']);
        $this->assertNull($json['next_date']);
        $this->assertSame('offer', $json['result']);
    }
}
