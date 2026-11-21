<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Cobertura: string implements HasLabel
{
    case Amplia = 'amplia';
    case Limitada = 'limitada';
    case ResponsabilidadCivil = 'rc';

    public function getLabel(): string
    {
        return match ($this) {
            self::Amplia => 'Amplia',
            self::Limitada => 'Limitada',
            self::ResponsabilidadCivil => 'Responsabilidad civil',
        };
    }
}
