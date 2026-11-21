<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recepcion extends Model
{
    protected $table = 'recepciones';

    protected $fillable = ['orden_compra_id', 'user_id', 'factura_xml', 'factura_uuid', 'factura_total', 'foto', 'notas'];

    protected function casts(): array
    {
        return ['factura_total' => 'float'];
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
