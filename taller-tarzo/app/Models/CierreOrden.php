<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Utilidad de la orden calculada por App\Services\Finanzas\Rentabilidad.
 */
class CierreOrden extends Model
{
    protected $table = 'cierres_orden';

    protected $fillable = [
        'orden_servicio_id', 'ingreso', 'ingreso_estimado', 'costo_refacciones', 'costo_materiales', 'costo_mano_obra',
        'costo_subcontratos', 'utilidad', 'margen', 'horas_reales', 'horas_estandar', 'calculado_at', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'ingreso' => 'float', 'ingreso_estimado' => 'boolean', 'costo_refacciones' => 'float', 'costo_materiales' => 'float',
            'costo_mano_obra' => 'float', 'costo_subcontratos' => 'float', 'utilidad' => 'float', 'margen' => 'float',
            'horas_reales' => 'float', 'horas_estandar' => 'float', 'calculado_at' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function costoTotal(): float
    {
        return $this->costo_refacciones + $this->costo_materiales + $this->costo_mano_obra + $this->costo_subcontratos;
    }
}
