<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Ciclo de vida de una versión de cotización.
 */
enum EstadoCotizacion: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Aprobada = 'aprobada';
    case AprobadaParcial = 'aprobada_parcial';
    case Rechazada = 'rechazada';
    case Reemplazada = 'reemplazada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Aprobada => 'Aprobada',
            self::AprobadaParcial => 'Aprobada parcialmente',
            self::Rechazada => 'Rechazada',
            self::Reemplazada => 'Reemplazada por otra versión',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Enviada => 'info',
            self::Aprobada => 'success',
            self::AprobadaParcial => 'warning',
            self::Rechazada => 'danger',
            self::Reemplazada => 'gray',
        };
    }

    /** Solo un borrador puede editarse libremente. */
    public function editable(): bool
    {
        return $this === self::Borrador;
    }

    public function esperaRespuesta(): bool
    {
        return $this === self::Enviada;
    }
}
