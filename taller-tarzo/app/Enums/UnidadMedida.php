<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Unidad de medida para inventario.
 */
enum UnidadMedida: string implements HasLabel
{
    case Pieza = 'pza';
    case Litro = 'l';
    case Galon = 'gal';
    case Kilogramo = 'kg';
    case Hoja = 'hoja';
    case Metro = 'm';
    case Juego = 'jgo';
    case Caja = 'caja';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pieza => 'Pieza',
            self::Litro => 'Litro',
            self::Galon => 'Galón',
            self::Kilogramo => 'Kilogramo',
            self::Hoja => 'Hoja',
            self::Metro => 'Metro',
            self::Juego => 'Juego',
            self::Caja => 'Caja',
        };
    }
}
