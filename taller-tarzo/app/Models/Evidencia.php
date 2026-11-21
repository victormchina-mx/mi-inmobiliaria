<?php

namespace App\Models;

use App\Enums\MomentoEvidencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto del proceso de reparación (transparencia para el cliente, respaldo ante la aseguradora).
 */
class Evidencia extends Model
{
    protected $table = 'evidencias';

    protected $fillable = ['orden_servicio_id', 'etapa_id', 'tarea_id', 'user_id', 'momento', 'descripcion', 'archivo', 'visible_cliente'];

    protected function casts(): array
    {
        return ['momento' => MomentoEvidencia::class, 'visible_cliente' => 'boolean', 'marcada_at' => 'datetime'];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
