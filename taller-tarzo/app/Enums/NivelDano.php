<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Nivel de daño de una pieza; cada uno tiene un factor de materiales en parámetros.
 */
enum NivelDano: string implements HasColor, HasLabel
{
    case Leve = 'leve';
    case Medio = 'medio';
    case Fuerte = 'fuerte';

    public function getLabel(): string
    {
        return match ($this) {
            self::Leve => 'Leve',
            self::Medio => 'Medio',
            self::Fuerte => 'Fuerte',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Leve => 'success',
            self::Medio => 'warning',
            self::Fuerte => 'danger',
        };
    }
}
