<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poliza extends Model
{
    protected $table = 'polizas';

    protected $fillable = ['vehiculo_id', 'aseguradora_id', 'numero', 'vigencia_inicio', 'vigencia_fin', 'cobertura', 'deducible_porcentaje', 'deducible_monto'];

    protected function casts(): array
    {
        return [
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
            'deducible_porcentaje' => 'decimal:2',
            'deducible_monto' => 'decimal:2',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function aseguradora(): BelongsTo
    {
        return $this->belongsTo(Aseguradora::class);
    }

    public function estaVigente(?\DateTimeInterface $fecha = null): bool
    {
        $fecha ??= now();

        return $this->vigencia_fin === null || $this->vigencia_fin->endOfDay()->gte($fecha);
    }
}
