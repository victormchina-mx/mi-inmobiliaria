<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Roles del taller (ver matriz de permisos en App\Support\MatrizPermisos).
 */
enum Rol: string implements HasLabel
{
    case Dueno = 'dueno';
    case Asesor = 'asesor';
    case Valuador = 'valuador';
    case JefeTaller = 'jefe_taller';
    case Tecnico = 'tecnico';
    case Almacen = 'almacen';
    case Contabilidad = 'contabilidad';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dueno => 'Dueño / gerente',
            self::Asesor => 'Asesor de servicio',
            self::Valuador => 'Valuador',
            self::JefeTaller => 'Jefe de taller',
            self::Tecnico => 'Técnico',
            self::Almacen => 'Almacén y compras',
            self::Contabilidad => 'Contabilidad y caja',
        };
    }
}
