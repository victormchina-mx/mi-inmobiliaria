<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Operación de hojalatería y pintura sobre una pieza.
 */
enum OperacionHyP: string implements HasLabel
{
    case Reparar = 'reparar';
    case Sustituir = 'sustituir';
    case Pintar = 'pintar';
    case DesmontarMontar = 'desmontar_montar';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reparar => 'Reparar y pintar',
            self::Sustituir => 'Sustituir y pintar',
            self::Pintar => 'Solo pintar',
            self::DesmontarMontar => 'Desmontar y montar',
        };
    }

    public function llevaPintura(): bool
    {
        return $this !== self::DesmontarMontar;
    }

    public function llevaRefaccion(): bool
    {
        return $this === self::Sustituir;
    }
}
