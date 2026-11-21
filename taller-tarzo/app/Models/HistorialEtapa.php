<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEtapa extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historial_etapas';

    protected $fillable = ['orden_servicio_id', 'etapa_anterior_id', 'etapa_id', 'user_id', 'comentario', 'excepcion'];

    protected function casts(): array
    {
        return ['excepcion' => 'boolean'];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    public function etapaAnterior(): BelongsTo
    {
        return $this->belongsTo(Etapa::class, 'etapa_anterior_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
