<?php

namespace App\Models;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPartida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Una versión del presupuesto de una orden (C1, C2…). Las versiones anteriores se conservan.
 */
class Cotizacion extends Model
{
    use LogsActivity;

    protected $table = 'cotizaciones';

    protected $fillable = ['orden_servicio_id', 'version', 'folio', 'estado', 'aseguradora_id', 'user_id', 'iva_tasa', 'vigencia_dias', 'notas'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoCotizacion::class,
            'iva_tasa' => 'float',
            'subtotal_mano_obra' => 'float',
            'subtotal_refacciones' => 'float',
            'subtotal_hojalateria_pintura' => 'float',
            'subtotal_otros' => 'float',
            'importe_cabina' => 'float',
            'subtotal' => 'float',
            'iva' => 'float',
            'total' => 'float',
            'monto_aseguradora' => 'float',
            'monto_cliente' => 'float',
            'deducible' => 'float',
            'enviada_at' => 'datetime',
            'respondida_at' => 'datetime',
            'recordatorio_at' => 'datetime',
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

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class)->orderBy('orden');
    }

    public function aprobaciones(): HasMany
    {
        return $this->hasMany(Aprobacion::class)->latest('id');
    }

    public function esSeguro(): bool
    {
        return $this->aseguradora_id !== null;
    }

    public function partidasPendientes(): HasMany
    {
        return $this->partidas()->where('estado', EstadoPartida::Pendiente->value);
    }

    /**
     * Huella del contenido que el cliente o ajustador ve al autorizar.
     */
    public function huella(): string
    {
        $this->loadMissing('partidas');

        $contenido = [
            'folio' => $this->folio,
            'partidas' => $this->partidas->map(fn (Partida $p) => [$p->id, $p->tipo->value, $p->descripcion, $p->paga->value, (string) $p->importe])->all(),
            'total' => (string) $this->total,
        ];

        return hash('sha256', json_encode($contenido, JSON_UNESCAPED_UNICODE));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['estado', 'total', 'enviada_at', 'respondida_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
