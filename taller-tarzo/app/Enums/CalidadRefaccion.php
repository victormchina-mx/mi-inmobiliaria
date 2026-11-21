<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Origen de la refacción.
 */
enum CalidadRefaccion: string implements HasLabel
{
    case Original = 'original';
    case Alternativa = 'alternativa';
    case Usada = 'usada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Original => 'Original (agencia)',
            self::Alternativa => 'Alternativa / genérica',
            self::Usada => 'Usada / recuperada',
        };
    }
}
