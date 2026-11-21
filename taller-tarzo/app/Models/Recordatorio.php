<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recordatorio extends Model
{
    protected $table = 'recordatorios';

    protected $fillable = ['vehiculo_id', 'cliente_id', 'regla_mantenimiento_id', 'tipo', 'titulo', 'fecha', 'km_estimado', 'referencia', 'estado', 'mensaje_id'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'km_estimado' => 'integer'];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaMantenimiento::class, 'regla_mantenimiento_id');
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class);
    }
}
