<?php

namespace App\Models;

use App\Enums\EstadoFactura;
use App\Enums\FormaPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * CFDI emitido: ingreso (I), nota de crédito (E) o complemento de pago (P).
 */
class Factura extends Model
{
    use LogsActivity;

    public const INGRESO = 'I';

    public const EGRESO = 'E';

    public const PAGO = 'P';

    protected $table = 'facturas';

    protected $fillable = [
        'tipo', 'serie', 'folio', 'orden_servicio_id', 'receptor_tipo', 'cliente_id', 'aseguradora_id',
        'receptor_rfc', 'receptor_nombre', 'receptor_regimen', 'receptor_cp', 'uso_cfdi', 'email',
        'metodo_pago', 'forma_pago', 'moneda', 'subtotal', 'descuento', 'iva', 'total', 'saldo', 'estado',
        'relacion_tipo', 'factura_relacionada_id', 'pago_id', 'vence', 'contrarrecibo', 'fecha_pago_compromiso',
        'notas', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoFactura::class,
            'forma_pago' => FormaPago::class,
            'subtotal' => 'float', 'descuento' => 'float', 'iva' => 'float', 'total' => 'float', 'saldo' => 'float',
            'vence' => 'date', 'fecha_pago_compromiso' => 'date',
            'timbrada_at' => 'datetime', 'cancelada_at' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class);
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(ConceptoFactura::class);
    }

    public function relacionada(): BelongsTo
    {
        return $this->belongsTo(self::class, 'factura_relacionada_id');
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(self::class, 'factura_relacionada_id')->where('tipo', self::EGRESO);
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(AplicacionPago::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Totales a partir de los conceptos (IVA calculado por concepto, como lo pide el CFDI).
     */
    public function recalcular(): self
    {
        $c = $this->conceptos()->get();
        $subtotal = round($c->sum('importe'), 2);
        $descuento = round($c->sum('descuento'), 2);
        $iva = round($c->sum('iva'), 2);

        $this->forceFill(['subtotal' => $subtotal, 'descuento' => $descuento, 'iva' => $iva, 'total' => round($subtotal - $descuento + $iva, 2)])->save();

        return $this;
    }

    public function getFolioCompletoAttribute(): string
    {
        return $this->serie.'-'.$this->folio;
    }

    public function etiquetaTipo(): string
    {
        return match ($this->tipo) {
            self::EGRESO => 'Nota de crédito',
            self::PAGO => 'Complemento de pago',
            default => 'Factura',
        };
    }

    public function editable(): bool
    {
        return in_array($this->estado, [EstadoFactura::Borrador, EstadoFactura::Error], true);
    }

    public function timbrada(): bool
    {
        return $this->estado === EstadoFactura::Timbrada;
    }

    /** Facturas de ingreso vigentes con saldo. */
    public function scopePorCobrar(Builder $query): Builder
    {
        return $query->where('tipo', self::INGRESO)->where('estado', EstadoFactura::Timbrada->value)->where('saldo', '>', 0.009);
    }

    public function diasVencida(): int
    {
        $base = $this->vence ?? $this->timbrada_at;

        return $base ? max(0, (int) $base->startOfDay()->diffInDays(today(), false)) : 0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['estado', 'uuid', 'total', 'saldo', 'motivo_cancelacion'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
