<?php

namespace App\Models;

use App\Enums\TipoDano;
use App\Enums\ZonaVehiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanoPrevio extends Model
{
    protected $table = 'danos_previos';

    protected $fillable = ['checkin_id', 'zona', 'tipo', 'descripcion', 'foto', 'orden'];

    protected function casts(): array
    {
        return ['zona' => ZonaVehiculo::class, 'tipo' => TipoDano::class];
    }

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(Checkin::class);
    }
}
