<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoFactura: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Timbrada = 'timbrada';
    case Error = 'error';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Timbrada => 'Timbrada',
            self::Error => 'Error al timbrar',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Timbrada => 'success',
            self::Error => 'danger',
            self::Cancelada => 'warning',
        };
    }
}
