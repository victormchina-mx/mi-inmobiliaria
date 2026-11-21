<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Categoría de un producto de almacén.
 */
enum CategoriaProducto: string implements HasLabel
{
    case Aceites = 'aceites';
    case Filtros = 'filtros';
    case Frenos = 'frenos';
    case Electrico = 'electrico';
    case Abrasivos = 'abrasivos';
    case Pinturas = 'pinturas';
    case Solventes = 'solventes';
    case Pastas = 'pastas';
    case Refacciones = 'refacciones';
    case Consumibles = 'consumibles';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aceites => 'Aceites y lubricantes',
            self::Filtros => 'Filtros',
            self::Frenos => 'Frenos',
            self::Electrico => 'Eléctrico',
            self::Abrasivos => 'Lijas y abrasivos',
            self::Pinturas => 'Pinturas y barnices',
            self::Solventes => 'Solventes y thinner',
            self::Pastas => 'Pastas y masillas',
            self::Refacciones => 'Refacciones',
            self::Consumibles => 'Consumibles',
            self::Otro => 'Otro',
        };
    }
}
