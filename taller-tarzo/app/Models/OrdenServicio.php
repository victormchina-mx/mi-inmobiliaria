<?php

namespace App\Models;

use App\Enums\TipoOrden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrdenServicio extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'folio', 'tipo', 'cliente_id', 'vehiculo_id', 'asesor_id', 'etapa_id',
        'aseguradora_id', 'poliza_id', 'numero_siniestro', 'kilometraje_ingreso',
        'motivo_ingreso', 'fecha_ingreso', 'etapa_desde', 'fecha_promesa', 'fecha_entrega', 'cerrada_at',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoOrden::class,
            'fecha_ingreso' => 'datetime',
            'etapa_desde' => 'datetime',
            'fecha_promesa' => 'datetime',
            'fecha_entrega' => 'datetime',
            'cerrada_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class);
    }

    public function poliza(): BelongsTo
    {
        return $this->belongsTo(Poliza::class);
    }

    public function historialEtapas(): HasMany
    {
        return $this->hasMany(HistorialEtapa::class)->latest('id');
    }

    /**
     * Días completos que lleva la orden en su etapa actual.
     */
    public function diasEnEtapa(): int
    {
        return (int) $this->etapa_desde->diffInDays(now());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'etapa_id', 'asesor_id', 'fecha_promesa', 'fecha_entrega', 'cerrada_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
