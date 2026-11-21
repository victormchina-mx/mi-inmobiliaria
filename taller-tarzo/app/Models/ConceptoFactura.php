<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConceptoFactura extends Model
{
    protected $table = 'conceptos_factura';

    public $timestamps = false;

    protected $fillable = [
        'factura_id', 'partida_id', 'clave_prod_serv', 'clave_unidad', 'unidad', 'no_identificacion', 'descripcion',
        'cantidad', 'valor_unitario', 'importe', 'descuento', 'iva_tasa', 'iva',
    ];

    protected function casts(): array
    {
        return ['cantidad' => 'float', 'valor_unitario' => 'float', 'importe' => 'float', 'descuento' => 'float', 'iva_tasa' => 'float', 'iva' => 'float'];
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function base(): float
    {
        return round($this->importe - $this->descuento, 2);
    }
}
