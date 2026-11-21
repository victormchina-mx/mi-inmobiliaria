<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Tecnico extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'tecnicos';

    protected $fillable = ['user_id', 'area_id', 'nombre', 'especialidad', 'costo_hora', 'capacidad_horas_dia', 'activo'];

    protected function casts(): array
    {
        return [
            'costo_hora' => 'decimal:2',
            'capacidad_horas_dia' => 'decimal:1',
            'activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
