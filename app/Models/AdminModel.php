<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table      = 'admin';
    protected $primaryKey = 'aid';
    protected $returnType = 'array';
    protected $allowedFields = ['auser', 'aemail', 'apass', 'adob', 'aphone'];

    protected $validationRules = [
        'auser'  => 'required|max_length[50]',
        'aemail' => 'required|valid_email',
        'apass'  => 'required|min_length[6]',
    ];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['apass'])) {
            $data['data']['apass'] = password_hash($data['data']['apass'], PASSWORD_BCRYPT);
        }

        return $data;
    }

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
}
