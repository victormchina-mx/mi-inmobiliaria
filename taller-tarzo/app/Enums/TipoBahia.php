<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoBahia: string implements HasLabel
{
    case Bahia = 'bahia';
    case Elevador = 'elevador';
    case CabinaPintura = 'cabina';
    case Preparacion = 'preparacion';
    case Patio = 'patio';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bahia => 'Bahía de trabajo',
            self::Elevador => 'Elevador',
            self::CabinaPintura => 'Cabina de pintura',
            self::Preparacion => 'Área de preparación',
            self::Patio => 'Patio / espera',
        };
    }
}
