<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operación de mano de obra con horas estándar (catálogo del taller).
 */
class Operacion extends Model
{
    protected $table = 'operaciones';

    protected $fillable = ['area_id', 'nombre', 'horas_estandar', 'activo'];

    protected function casts(): array
    {
        return ['horas_estandar' => 'decimal:2', 'activo' => 'boolean'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
