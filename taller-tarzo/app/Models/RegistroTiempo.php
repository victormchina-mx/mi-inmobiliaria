<?php

namespace App\Models;

use App\Enums\MotivoPausa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroTiempo extends Model
{
    protected $table = 'registros_tiempo';

    protected $fillable = ['tarea_id', 'tecnico_id', 'inicio', 'fin', 'minutos', 'motivo_pausa'];

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime', 'minutos' => 'integer', 'motivo_pausa' => MotivoPausa::class];
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
