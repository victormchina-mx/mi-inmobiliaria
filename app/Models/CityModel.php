<?php

namespace App\Models;

use CodeIgniter\Model;

class CityModel extends Model
{
    protected $table      = 'city';
    protected $primaryKey = 'cid';
    protected $returnType = 'array';
    protected $allowedFields = ['cname', 'sid'];

    protected $validationRules = [
        'cname' => 'required|max_length[100]',
        'sid'   => 'required|is_natural_no_zero',
    ];

    public function withState(): static
    {
        return $this->select('city.*, state.sname')
                    ->join('state', 'state.sid = city.sid');
    }
}
