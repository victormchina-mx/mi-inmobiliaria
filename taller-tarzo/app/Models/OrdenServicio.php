<?php

namespace App\Models;

use App\Enums\EstadoCotizacion;
use App\Enums\TipoOrden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function siniestro(): HasOne
    {
        return $this->hasOne(Siniestro::class);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class)->orderByDesc('version');
    }

    /** Versión más reciente del presupuesto. */
    public function cotizacionActual(): HasOne
    {
        return $this->hasOne(Cotizacion::class)->latestOfMany('version');
    }

    public function checkin(): HasOne
    {
        return $this->hasOne(Checkin::class);
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class)->latest();
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class)->orderBy('posicion')->orderBy('id');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class)->latest();
    }

    public function requisiciones(): HasMany
    {
        return $this->hasMany(Requisicion::class);
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class)->latest('id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class)->latest('id');
    }

    /** Último checklist del tipo indicado (calidad o entrega). */
    public function checklist(string $tipo): ?Checklist
    {
        return $this->checklists()->where('tipo', $tipo)->first();
    }

    public function tieneCotizacionAprobada(): bool
    {
        return $this->cotizaciones()
            ->whereIn('estado', [EstadoCotizacion::Aprobada->value, EstadoCotizacion::AprobadaParcial->value])
            ->exists();
    }

    public function historialEtapas(): HasMany
    {
        return $this->hasMany(HistorialEtapa::class)->with(['etapa', 'etapaAnterior', 'usuario'])->latest('id');
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
