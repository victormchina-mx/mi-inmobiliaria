<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoOrden: string implements HasColor, HasLabel
{
    case Particular = 'particular';
    case Seguro = 'seguro';
    case Flotilla = 'flotilla';
    case Garantia = 'garantia';
    case Interna = 'interna';

    public function getLabel(): string
    {
        return match ($this) {
            self::Particular => 'Particular',
            self::Seguro => 'Seguro',
            self::Flotilla => 'Flotilla',
            self::Garantia => 'Garantía',
            self::Interna => 'Interna',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Particular => 'gray',
            self::Seguro => 'info',
            self::Flotilla => 'primary',
            self::Garantia => 'warning',
            self::Interna => 'gray',
        };
    }
}
