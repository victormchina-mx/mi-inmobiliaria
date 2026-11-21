<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraPartida extends Model
{
    protected $table = 'orden_compra_partidas';

    protected $fillable = ['orden_compra_id', 'requisicion_id', 'producto_id', 'descripcion', 'numero_parte', 'cantidad', 'costo_unitario'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'float',
            'cantidad_recibida' => 'float',
            'costo_unitario' => 'float',
            'costo_real' => 'float',
        ];
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function pendiente(): float
    {
        return max(0, round($this->cantidad - $this->cantidad_recibida, 3));
    }
}
