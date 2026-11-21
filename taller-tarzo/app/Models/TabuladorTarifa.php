<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabuladorTarifa extends Model
{
    public $timestamps = false;

    protected $table = 'tabulador_tarifas';

    protected $fillable = ['tabulador_id', 'area_id', 'tarifa_hora'];

    protected function casts(): array
    {
        return ['tarifa_hora' => 'float'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
