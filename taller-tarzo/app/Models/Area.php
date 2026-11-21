<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Area extends Model
{
    use LogsActivity;

    protected $table = 'areas';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'tarifa_hora', 'activo'];

    protected function casts(): array
    {
        return ['tarifa_hora' => 'decimal:2', 'activo' => 'boolean'];
    }

    public function bahias(): HasMany
    {
        return $this->hasMany(Bahia::class);
    }

    public function tecnicos(): HasMany
    {
        return $this->hasMany(Tecnico::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
