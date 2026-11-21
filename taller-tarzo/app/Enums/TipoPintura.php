<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tipo de acabado; cada uno tiene su costo de materiales por m² en parámetros.
 */
enum TipoPintura: string implements HasLabel
{
    case Solida = 'solida';
    case Metalica = 'metalica';
    case Perlada = 'perlada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Solida => 'Sólida',
            self::Metalica => 'Metálica',
            self::Perlada => 'Perlada / tricapa',
        };
    }
}
