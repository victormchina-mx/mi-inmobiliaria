<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kárdex: cada renglón es inmutable (se corrige con otro movimiento, nunca editando).
 */
class MovimientoInventario extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id', 'tipo', 'cantidad', 'costo_unitario', 'costo_total', 'existencia_resultante', 'costo_promedio_resultante',
        'orden_servicio_id', 'recepcion_id', 'gasto_general', 'referencia', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'cantidad' => 'float',
            'costo_unitario' => 'float',
            'costo_total' => 'float',
            'existencia_resultante' => 'float',
            'costo_promedio_resultante' => 'float',
            'gasto_general' => 'boolean',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
