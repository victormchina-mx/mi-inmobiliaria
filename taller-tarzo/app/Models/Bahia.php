<?php

namespace App\Models;

use App\Enums\TipoBahia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bahia extends Model
{
    protected $table = 'bahias';

    protected $fillable = ['area_id', 'nombre', 'tipo', 'activo'];

    protected function casts(): array
    {
        return ['tipo' => TipoBahia::class, 'activo' => 'boolean'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
