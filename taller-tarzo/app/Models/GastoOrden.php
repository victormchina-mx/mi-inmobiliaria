<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subcontrato o gasto directo de una orden (grúa, rectificadora, cristales…), sin IVA.
 */
class GastoOrden extends Model
{
    protected $table = 'gastos_orden';

    protected $fillable = ['orden_servicio_id', 'concepto', 'proveedor', 'monto', 'factura_proveedor', 'fecha', 'user_id'];

    protected function casts(): array
    {
        return ['monto' => 'float', 'fecha' => 'date'];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }
}
