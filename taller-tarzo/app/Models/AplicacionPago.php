<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionPago extends Model
{
    protected $table = 'aplicaciones_pago';

    protected $fillable = ['pago_id', 'factura_id', 'monto', 'saldo_anterior', 'parcialidad'];

    protected function casts(): array
    {
        return ['monto' => 'float', 'saldo_anterior' => 'float'];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
}
