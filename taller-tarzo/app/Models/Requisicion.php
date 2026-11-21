<?php

namespace App\Models;

use App\Enums\EstadoRequisicion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Pieza que una orden necesita y hay que conseguir (la compra el taller o la surte la aseguradora).
 */
class Requisicion extends Model
{
    protected $table = 'requisiciones';

    protected $fillable = ['orden_servicio_id', 'partida_id', 'producto_id', 'descripcion', 'numero_parte', 'cantidad', 'surte', 'estado', 'costo_real', 'recibida_at'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoRequisicion::class,
            'cantidad' => 'float',
            'costo_real' => 'float',
            'recibida_at' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function partidaCompra(): HasOne
    {
        return $this->hasOne(OrdenCompraPartida::class)->latestOfMany();
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', [EstadoRequisicion::Pendiente->value, EstadoRequisicion::Ordenada->value]);
    }
}
