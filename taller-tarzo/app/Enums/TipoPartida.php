<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Tipo de partida de una cotización.
 */
enum TipoPartida: string implements HasColor, HasLabel
{
    case ManoObra = 'mano_obra';
    case Refaccion = 'refaccion';
    case HojalateriaPintura = 'hojalateria_pintura';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::ManoObra => 'Mano de obra',
            self::Refaccion => 'Refacción',
            self::HojalateriaPintura => 'Hojalatería y pintura',
            self::Otro => 'Otro / subcontrato',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ManoObra => 'info',
            self::Refaccion => 'warning',
            self::HojalateriaPintura => 'primary',
            self::Otro => 'gray',
        };
    }
}
