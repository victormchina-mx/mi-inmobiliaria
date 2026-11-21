<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Quién paga una partida en una orden de seguro.
 */
enum Paga: string implements HasColor, HasLabel
{
    case Cliente = 'cliente';
    case Aseguradora = 'aseguradora';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Aseguradora => 'Aseguradora',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cliente => 'gray',
            self::Aseguradora => 'info',
        };
    }
}
