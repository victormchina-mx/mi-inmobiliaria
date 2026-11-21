<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaMantenimiento extends Model
{
    protected $table = 'reglas_mantenimiento';

    protected $fillable = ['nombre', 'cada_km', 'cada_meses', 'activo'];

    protected function casts(): array
    {
        return ['cada_km' => 'integer', 'cada_meses' => 'integer', 'activo' => 'boolean'];
    }
}
