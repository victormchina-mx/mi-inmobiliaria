<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'user';
    protected $primaryKey = 'uid';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'uname', 'uemail', 'uphone', 'upass', 'utype', 'uimage',
    ];

    protected $validationRules = [
        'uname'  => 'required|max_length[100]',
        'uemail' => 'required|valid_email|max_length[100]',
        'uphone' => 'permit_empty|max_length[20]',
        'upass'  => 'required|min_length[6]',
        'utype'  => 'required|in_list[user,agent,builder]',
    ];

    /** Scope: sólo usuarios tipo "user" */
    public function users(): static
    {
        return $this->where('utype', 'user');
    }

    /** Scope: sólo agentes */
    public function agents(): static
    {
        return $this->where('utype', 'agent');
    }

    /** Scope: sólo builders */
    public function builders(): static
    {
        return $this->where('utype', 'builder');
    }

    /** Hashea la contraseña antes de insertar/actualizar */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['upass'])) {
            $data['data']['upass'] = password_hash($data['data']['upass'], PASSWORD_BCRYPT);
        }

        return $data;
    }

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
}
