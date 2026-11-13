<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Inmobiliarias (tabla agency, app/Database/sql/2026-11-agencies.sql).
 * Reglas y marca (logo y colores) en App\Libraries\Agency.
 */
class AgencyModel extends Model
{
    protected $table         = 'agency';
    protected $primaryKey    = 'agency_id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'slug', 'logo', 'color_primary', 'color_accent', 'tagline',
        'phone', 'whatsapp', 'email', 'website', 'manager_uid', 'status',
    ];
}
