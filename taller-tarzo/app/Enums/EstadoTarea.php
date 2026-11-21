<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoTarea: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Pausada = 'pausada';
    case Terminada = 'terminada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En proceso',
            self::Pausada => 'Pausada',
            self::Terminada => 'Terminada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::EnProceso => 'primary',
            self::Pausada => 'warning',
            self::Terminada => 'success',
        };
    }
}
