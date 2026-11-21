<?php

namespace App\Models;

use App\Enums\FormaPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Dinero recibido (caja o transferencia). Se aplica a una o varias facturas; lo no aplicado es anticipo/saldo a favor.
 */
class Pago extends Model
{
    use LogsActivity;

    protected $table = 'pagos';

    protected $fillable = [
        'folio', 'cliente_id', 'aseguradora_id', 'orden_servicio_id', 'fecha', 'forma_pago', 'monto', 'por_aplicar',
        'es_anticipo', 'referencia', 'notas', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime', 'forma_pago' => FormaPago::class, 'monto' => 'float', 'por_aplicar' => 'float',
            'es_anticipo' => 'boolean', 'cancelado_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(AplicacionPago::class);
    }

    public function complemento(): HasOne
    {
        return $this->hasOne(Factura::class)->where('tipo', Factura::PAGO)->latestOfMany();
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pagador(): string
    {
        return $this->aseguradora?->nombre ?? $this->cliente?->nombre_completo ?? '—';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['monto', 'por_aplicar', 'cancelado_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
