<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoPersona: string implements HasLabel
{
    case Fisica = 'fisica';
    case Moral = 'moral';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fisica => 'Persona física',
            self::Moral => 'Persona moral',
        };
    }
}
