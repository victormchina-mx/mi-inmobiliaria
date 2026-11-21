<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Parámetros numéricos del taller (costos de materiales, factores, cabina, IVA…).
 */
class Parametro extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'parametros';

    protected $primaryKey = 'clave';

    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor', 'descripcion', 'unidad'];

    protected function casts(): array
    {
        return ['valor' => 'float'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('parametros'));
        static::deleted(fn () => Cache::forget('parametros'));
    }

    public static function valor(string $clave, float $defecto = 0): float
    {
        $todos = Cache::remember('parametros', 300, fn () => static::query()->pluck('valor', 'clave')->map(fn ($v) => (float) $v)->all());

        return $todos[$clave] ?? $defecto;
    }
}
