<?php

namespace App\Models;

use App\Enums\EstadoTarea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trabajo concreto para un técnico (sale de una partida autorizada o se agrega a mano).
 */
class Tarea extends Model
{
    protected $table = 'tareas';

    protected $fillable = ['orden_servicio_id', 'partida_id', 'area_id', 'tecnico_id', 'bahia_id', 'descripcion', 'horas_estandar', 'programada_para', 'posicion'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoTarea::class,
            'horas_estandar' => 'float',
            'horas_reales' => 'float',
            'programada_para' => 'date',
            'iniciada_at' => 'datetime',
            'terminada_at' => 'datetime',
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

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function bahia(): BelongsTo
    {
        return $this->belongsTo(Bahia::class);
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroTiempo::class)->orderBy('inicio');
    }

    public function registroAbierto(): HasOne
    {
        return $this->hasOne(RegistroTiempo::class)->whereNull('fin')->latestOfMany('inicio');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class);
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->where('estado', '!=', EstadoTarea::Terminada->value);
    }

    /**
     * Horas estándar ÷ horas reales: más de 1 significa que se hizo más rápido que el estándar.
     */
    public function eficiencia(): ?float
    {
        return $this->horas_reales > 0 ? round($this->horas_estandar / $this->horas_reales, 2) : null;
    }

    /**
     * Horas reales acumuladas, incluyendo el tramo en curso.
     */
    public function horasAcumuladas(): float
    {
        $abierto = $this->registroAbierto;

        return round($this->horas_reales + ($abierto ? $abierto->inicio->diffInMinutes(now()) / 60 : 0), 2);
    }
}
