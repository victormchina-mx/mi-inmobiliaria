<?php

namespace App\Models;

use App\Enums\CategoriaProducto;
use App\Enums\UnidadMedida;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Producto de almacén (consumibles, pintura, refacciones de stock).
 * La existencia y el costo promedio solo cambian a través de App\Services\Inventario\Inventario.
 */
class Producto extends Model
{
    use LogsActivity;

    protected $table = 'productos';

    protected $fillable = ['sku', 'codigo_barras', 'nombre', 'categoria', 'unidad', 'ubicacion', 'stock_minimo', 'stock_maximo', 'proveedor_id', 'activo'];

    protected function casts(): array
    {
        return [
            'categoria' => CategoriaProducto::class,
            'unidad' => UnidadMedida::class,
            'existencia' => 'float',
            'costo_promedio' => 'float',
            'stock_minimo' => 'float',
            'stock_maximo' => 'float',
            'activo' => 'boolean',
            'ultimo_movimiento_at' => 'datetime',
            'alerta_minimo_at' => 'datetime',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class)->latest('id');
    }

    public function bajoMinimo(): bool
    {
        return $this->stock_minimo > 0 && $this->existencia < $this->stock_minimo;
    }

    /**
     * Cantidad sugerida para reponer hasta el máximo (o al doble del mínimo si no hay máximo).
     */
    public function sugerenciaCompra(): float
    {
        $objetivo = $this->stock_maximo > 0 ? $this->stock_maximo : $this->stock_minimo * 2;

        return max(0, round($objetivo - (float) $this->existencia, 3));
    }

    public function valorInventario(): float
    {
        return round($this->existencia * $this->costo_promedio, 2);
    }

    public function scopeBajoMinimo(Builder $query): Builder
    {
        return $query->where('stock_minimo', '>', 0)->whereColumn('existencia', '<', 'stock_minimo');
    }

    public function scopeSinMovimiento(Builder $query, int $dias = 90): Builder
    {
        return $query->where('existencia', '>', 0)
            ->where(fn (Builder $q) => $q->whereNull('ultimo_movimiento_at')->orWhere('ultimo_movimiento_at', '<', now()->subDays($dias)));
    }

    public static function buscarCodigo(string $codigo): ?self
    {
        $codigo = trim($codigo);

        return static::where('sku', $codigo)->orWhere('codigo_barras', $codigo)->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
