<?php

namespace App\Filament\Resources\Cotizaciones\Support;

use App\Models\Cotizacion;
use App\Services\Cotizacion\Contexto;

/**
 * Contexto de cálculo memorizado por petición: el formulario lo consulta por cada partida.
 */
final class ContextoFormulario
{
    /** @var array<int, Contexto> */
    private static array $cache = [];

    public static function para(?Cotizacion $cotizacion): ?Contexto
    {
        if (! $cotizacion) {
            return null;
        }

        return self::$cache[$cotizacion->id] ??= Contexto::paraCotizacion($cotizacion->loadMissing('orden.siniestro'));
    }

    public static function olvidar(): void
    {
        self::$cache = [];
    }
}
