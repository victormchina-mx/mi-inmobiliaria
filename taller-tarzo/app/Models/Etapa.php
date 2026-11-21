<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Columna del tablero Kanban (Recepción → … → Listo para entrega).
 */
class Etapa extends Model
{
    public const RECEPCION = 'recepcion';

    public const LISTO_ENTREGA = 'listo_entrega';

    public const ENTREGADO = 'entregado';

    protected $table = 'etapas';

    protected $fillable = ['clave', 'nombre', 'orden', 'color', 'dias_max', 'limite_autos', 'notifica_cliente', 'es_final'];

    protected function casts(): array
    {
        return ['notifica_cliente' => 'boolean', 'es_final' => 'boolean'];
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden');
    }

    public static function inicial(): self
    {
        return static::where('clave', self::RECEPCION)->firstOrFail();
    }
}
