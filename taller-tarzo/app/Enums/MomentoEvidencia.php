<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Momento del proceso que documenta una foto (respaldo ante la aseguradora).
 */
enum MomentoEvidencia: string implements HasLabel
{
    case Danada = 'danada';
    case Desmontada = 'desmontada';
    case NuevaInstalada = 'nueva_instalada';
    case Proceso = 'proceso';
    case Fondo = 'fondo';
    case Pintura = 'pintura';
    case Terminado = 'terminado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Danada => 'Pieza dañada',
            self::Desmontada => 'Pieza desmontada',
            self::NuevaInstalada => 'Pieza nueva instalada',
            self::Proceso => 'En proceso',
            self::Fondo => 'Fondo / primer',
            self::Pintura => 'Pintura',
            self::Terminado => 'Terminado',
        };
    }
}
