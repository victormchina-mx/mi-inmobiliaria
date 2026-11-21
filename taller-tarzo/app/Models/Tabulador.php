<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Condiciones pactadas con una aseguradora: tarifas por área y descuentos.
 */
class Tabulador extends Model
{
    use LogsActivity;

    protected $table = 'tabuladores';

    protected $fillable = ['aseguradora_id', 'descuento_refacciones', 'descuento_mano_obra', 'factor_materiales', 'conceptos_no_cubiertos', 'notas'];

    protected function casts(): array
    {
        return [
            'descuento_refacciones' => 'float',
            'descuento_mano_obra' => 'float',
            'factor_materiales' => 'float',
        ];
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class);
    }

    public function tarifas(): HasMany
    {
        return $this->hasMany(TabuladorTarifa::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
