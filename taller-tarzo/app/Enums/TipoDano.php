<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoDano: string implements HasLabel
{
    case Rayon = 'rayon';
    case Golpe = 'golpe';
    case Abolladura = 'abolladura';
    case Estrellado = 'estrellado';
    case Despintado = 'despintado';
    case Roto = 'roto';
    case Faltante = 'faltante';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::Rayon => 'Rayón',
            self::Golpe => 'Golpe',
            self::Abolladura => 'Abolladura',
            self::Estrellado => 'Estrellado',
            self::Despintado => 'Despintado / quemado',
            self::Roto => 'Roto',
            self::Faltante => 'Faltante',
            self::Otro => 'Otro',
        };
    }
}
