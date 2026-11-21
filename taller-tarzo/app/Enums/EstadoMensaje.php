<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado de entrega de un mensaje.
 */
enum EstadoMensaje: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Enviado = 'enviado';
    case Entregado = 'entregado';
    case Leido = 'leido';
    case Fallido = 'fallido';
    case Omitido = 'omitido';
    case Recibido = 'recibido';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Enviado => 'Enviado',
            self::Entregado => 'Entregado',
            self::Leido => 'Leído',
            self::Fallido => 'Falló',
            self::Omitido => 'No enviado',
            self::Recibido => 'Recibido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Enviado => 'info',
            self::Entregado => 'primary',
            self::Leido => 'success',
            self::Fallido => 'danger',
            self::Omitido => 'warning',
            self::Recibido => 'info',
        };
    }
}
