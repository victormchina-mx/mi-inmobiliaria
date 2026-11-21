<?php

namespace App\Models;

use App\Enums\CalidadRefaccion;
use App\Enums\Disponibilidad;
use App\Enums\EstadoPartida;
use App\Enums\NivelDano;
use App\Enums\OperacionHyP;
use App\Enums\Paga;
use App\Enums\TipoPartida;
use App\Enums\TipoPintura;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partida extends Model
{
    protected $table = 'partidas';

    protected $fillable = [
        'cotizacion_id', 'orden', 'tipo', 'descripcion', 'area_id', 'operacion_id', 'pieza_id', 'diagnostico_id',
        'horas', 'operacion_hyp', 'nivel_dano', 'tipo_pintura', 'horas_laminado', 'horas_pintura', 'materiales_manual',
        'cantidad', 'costo_unitario', 'margen', 'precio_manual', 'numero_parte', 'proveedor', 'disponibilidad', 'calidad',
        'paga', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPartida::class,
            'operacion_hyp' => OperacionHyP::class,
            'nivel_dano' => NivelDano::class,
            'tipo_pintura' => TipoPintura::class,
            'disponibilidad' => Disponibilidad::class,
            'calidad' => CalidadRefaccion::class,
            'paga' => Paga::class,
            'estado' => EstadoPartida::class,
            'horas' => 'float',
            'horas_laminado' => 'float',
            'horas_pintura' => 'float',
            'materiales_manual' => 'float',
            'cantidad' => 'float',
            'costo_unitario' => 'float',
            'margen' => 'float',
            'precio_manual' => 'float',
            'tarifa_aplicada' => 'float',
            'importe_materiales' => 'float',
            'precio_unitario' => 'float',
            'importe' => 'float',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function pieza(): BelongsTo
    {
        return $this->belongsTo(PiezaCarroceria::class, 'pieza_id');
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class);
    }

    public function estaAprobada(): bool
    {
        return $this->estado === EstadoPartida::Aprobada;
    }
}
