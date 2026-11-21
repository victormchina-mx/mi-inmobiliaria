<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncuestaNps extends Model
{
    protected $table = 'encuestas_nps';

    protected $fillable = ['orden_servicio_id', 'cliente_id', 'mensaje_id', 'calificacion', 'comentario', 'enviada_at', 'respondida_at', 'seguimiento_user_id', 'seguimiento_notas', 'seguimiento_at'];

    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
            'enviada_at' => 'datetime',
            'respondida_at' => 'datetime',
            'seguimiento_at' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsableSeguimiento(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seguimiento_user_id');
    }

    /** promotor (9-10), pasivo (7-8) o detractor (0-6). */
    public function grupo(): ?string
    {
        return match (true) {
            $this->calificacion === null => null,
            $this->calificacion >= 9 => 'promotor',
            $this->calificacion >= 7 => 'pasivo',
            default => 'detractor',
        };
    }

    /**
     * NPS = % promotores − % detractores (de −100 a 100).
     *
     * @param  iterable<int|null>  $calificaciones
     */
    public static function calcular(iterable $calificaciones): ?int
    {
        $c = collect($calificaciones)->filter(fn ($v) => $v !== null);

        if ($c->isEmpty()) {
            return null;
        }

        return (int) round(($c->filter(fn ($v) => $v >= 9)->count() - $c->filter(fn ($v) => $v <= 6)->count()) / $c->count() * 100);
    }
}
