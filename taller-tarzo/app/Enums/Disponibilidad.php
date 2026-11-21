<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Disponibilidad de una refacción al cotizar.
 */
enum Disponibilidad: string implements HasColor, HasLabel
{
    case EnStock = 'en_stock';
    case PorPedir = 'por_pedir';
    case PorConfirmar = 'por_confirmar';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnStock => 'En almacén',
            self::PorPedir => 'Por pedir',
            self::PorConfirmar => 'Por confirmar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnStock => 'success',
            self::PorPedir => 'warning',
            self::PorConfirmar => 'gray',
        };
    }
}
