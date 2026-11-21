<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrdenCompra extends Model
{
    use LogsActivity;

    protected $table = 'ordenes_compra';

    protected $fillable = ['folio', 'proveedor_id', 'estado', 'fecha_estimada', 'notas', 'user_id'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoOrdenCompra::class,
            'fecha_estimada' => 'date',
            'subtotal' => 'float',
            'enviada_at' => 'datetime',
            'recibida_at' => 'datetime',
            'alerta_retraso_at' => 'datetime',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(OrdenCompraPartida::class);
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class)->latest('id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function atrasada(): bool
    {
        return $this->estado->abierta() && $this->estado !== EstadoOrdenCompra::Borrador
            && $this->fecha_estimada !== null && $this->fecha_estimada->lt(today());
    }

    public function recalcularSubtotal(): void
    {
        $this->forceFill(['subtotal' => round($this->partidas()->get()->sum(fn ($p) => $p->cantidad * $p->costo_unitario), 2)])->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['estado', 'fecha_estimada', 'proveedor_id'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
