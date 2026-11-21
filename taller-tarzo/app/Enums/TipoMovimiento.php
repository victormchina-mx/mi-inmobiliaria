<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Tipo de movimiento de inventario.
 */
enum TipoMovimiento: string implements HasColor, HasLabel
{
    case Entrada = 'entrada';
    case Salida = 'salida';
    case Ajuste = 'ajuste';
    case Devolucion = 'devolucion';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida a orden',
            self::Ajuste => 'Ajuste',
            self::Devolucion => 'Devolución',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Salida => 'warning',
            self::Ajuste => 'gray',
            self::Devolucion => 'info',
        };
    }
}
