<?php

namespace App\Models;

use CodeIgniter\Model;

class StateModel extends Model
{
    protected $table      = 'state';
    protected $primaryKey = 'sid';
    protected $returnType = 'array';
    protected $allowedFields = ['sname'];

    protected $validationRules = [
        'sname' => 'required|max_length[100]',
    ];
}
