<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Respuesta de un cliente o ajustador a una cotización, con su evidencia.
 */
class Aprobacion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'aprobaciones';

    protected $fillable = ['cotizacion_id', 'rol', 'canal', 'nombre', 'user_id', 'ip', 'dispositivo', 'huella', 'partidas_aprobadas', 'partidas_rechazadas', 'comentario'];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
