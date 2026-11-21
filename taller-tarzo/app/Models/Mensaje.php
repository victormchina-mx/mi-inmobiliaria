<?php

namespace App\Models;

use App\Enums\EstadoMensaje;
use App\Enums\EventoMensaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje enviado al cliente o recibido de él (WhatsApp o correo).
 */
class Mensaje extends Model
{
    protected $table = 'mensajes';

    protected $fillable = [
        'direccion', 'cliente_id', 'orden_servicio_id', 'evento', 'canal', 'destino', 'asunto', 'cuerpo',
        'estado', 'meta', 'proveedor_id', 'error', 'intentos', 'user_id', 'enviado_at', 'entregado_at', 'leido_at',
    ];

    protected function casts(): array
    {
        return [
            'evento' => EventoMensaje::class,
            'estado' => EstadoMensaje::class,
            'meta' => 'array',
            'enviado_at' => 'datetime',
            'entregado_at' => 'datetime',
            'leido_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esEntrante(): bool
    {
        return $this->direccion === 'entrante';
    }
}
