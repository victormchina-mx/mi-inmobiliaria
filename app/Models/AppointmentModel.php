<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Citas de la agenda compartida (tabla appointment,
 * app/Database/sql/2026-11-appointments.sql). Reglas en App\Libraries\Agenda.
 */
class AppointmentModel extends Model
{
    protected $table         = 'appointment';
    protected $primaryKey    = 'apid';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pid', 'starts_at', 'ends_at', 'shower_uid', 'created_by',
        'client_name', 'client_phone', 'client_email', 'client_notes',
        'result', 'comments', 'next_action', 'next_date',
    ];

    /**
     * Cita con su inmueble, el responsable (dueño de la propiedad) y el
     * agente que muestra.
     */
    public function withDetails(): static
    {
        return $this->select('appointment.*, property.title, property.location, property.city, property.state, property.uid AS owner_uid, '
                . 'owner.uname AS owner_name, owner.uemail AS owner_email, shower.uname AS shower_name, shower.uemail AS shower_email')
            ->join('property', 'property.pid = appointment.pid', 'left')
            ->join('user owner', 'owner.uid = property.uid', 'left')
            ->join('user shower', 'shower.uid = appointment.shower_uid', 'left');
    }

    /** Citas que se empalman con [inicio, fin), opcionalmente sin contar una. */
    public function overlapping(string $start, string $end, ?int $except = null): static
    {
        $this->where('appointment.starts_at <', $end)->where('appointment.ends_at >', $start);
        if ($except) {
            $this->where('appointment.apid !=', $except);
        }

        return $this;
    }
}
