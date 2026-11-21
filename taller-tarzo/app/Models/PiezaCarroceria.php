<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiezaCarroceria extends Model
{
    protected $table = 'piezas_carroceria';

    protected $fillable = ['nombre', 'superficie_m2', 'horas_laminado_ref', 'horas_pintura_ref', 'activo'];

    protected function casts(): array
    {
        return [
            'superficie_m2' => 'float',
            'horas_laminado_ref' => 'float',
            'horas_pintura_ref' => 'float',
            'activo' => 'boolean',
        ];
    }
}
