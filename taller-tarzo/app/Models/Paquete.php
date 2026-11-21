<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Paquete de servicio (afinación, frenos…): plantilla de partidas que se agrega a una cotización.
 */
class Paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = ['nombre', 'descripcion', 'partidas', 'activo'];

    protected function casts(): array
    {
        return ['partidas' => 'array', 'activo' => 'boolean'];
    }
}
