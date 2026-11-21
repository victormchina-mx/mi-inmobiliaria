<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tipo de garantía de un trabajo.
 */
enum TipoGarantia: string implements HasLabel
{
    case Mecanica = 'mecanica';
    case Pintura = 'pintura';
    case Refacciones = 'refacciones';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mecanica => 'Mecánica',
            self::Pintura => 'Hojalatería y pintura',
            self::Refacciones => 'Refacciones',
        };
    }
}
