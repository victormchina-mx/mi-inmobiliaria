<?php

namespace App\Models;

use App\Enums\TipoGarantia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Garantia extends Model
{
    protected $table = 'garantias';

    protected $fillable = ['orden_servicio_id', 'vehiculo_id', 'tipo', 'descripcion', 'inicia', 'vence', 'km_inicial', 'km_limite', 'estado', 'orden_reclamo_id'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoGarantia::class,
            'inicia' => 'date',
            'vence' => 'date',
            'km_inicial' => 'integer',
            'km_limite' => 'integer',
            'alerta_at' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function ordenReclamo(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_reclamo_id');
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', 'vigente')->whereDate('vence', '>=', today());
    }

    public function vigente(?int $kmActual = null): bool
    {
        return $this->estado === 'vigente' && $this->vence->gte(today())
            && ($this->km_limite === null || $kmActual === null || $kmActual <= $this->km_limite);
    }
}
