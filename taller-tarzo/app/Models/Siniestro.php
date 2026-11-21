<?php

namespace App\Models;

use App\Enums\EstatusSiniestro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Expediente del siniestro de una orden de seguro.
 */
class Siniestro extends Model
{
    use LogsActivity;

    protected $table = 'siniestros';

    protected $fillable = [
        'orden_servicio_id', 'aseguradora_id', 'numero', 'ajustador_nombre', 'ajustador_telefono', 'ajustador_email',
        'deducible_tipo', 'deducible_valor', 'valor_comercial', 'demerito', 'salvamento',
        'orden_admision', 'folio_autorizacion', 'estatus', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'estatus' => EstatusSiniestro::class,
            'deducible_valor' => 'float',
            'valor_comercial' => 'float',
            'demerito' => 'float',
            'salvamento' => 'float',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class);
    }

    /**
     * Deducible en pesos (con IVA): monto fijo, o porcentaje del valor comercial.
     */
    public function deducibleMonto(): float
    {
        if ($this->deducible_tipo === 'porcentaje') {
            return round(($this->valor_comercial ?? 0) * ($this->deducible_valor / 100), 2);
        }

        return round((float) $this->deducible_valor, 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
