<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado de una pieza por surtir para una orden.
 */
enum EstadoRequisicion: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Ordenada = 'ordenada';
    case Recibida = 'recibida';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Por pedir',
            self::Ordenada => 'Pedida',
            self::Recibida => 'Recibida',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Ordenada => 'info',
            self::Recibida => 'success',
            self::Cancelada => 'gray',
        };
    }
}
