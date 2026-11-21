<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Avance de una orden de compra.
 */
enum EstadoOrdenCompra: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Confirmada = 'confirmada';
    case EnTransito = 'en_transito';
    case RecibidaParcial = 'recibida_parcial';
    case Recibida = 'recibida';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Confirmada => 'Confirmada',
            self::EnTransito => 'En tránsito',
            self::RecibidaParcial => 'Recibida parcial',
            self::Recibida => 'Recibida',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Enviada => 'info',
            self::Confirmada => 'info',
            self::EnTransito => 'primary',
            self::RecibidaParcial => 'warning',
            self::Recibida => 'success',
            self::Cancelada => 'danger',
        };
    }

    /** Puede recibir mercancía. */
    public function recibible(): bool
    {
        return in_array($this, [self::Enviada, self::Confirmada, self::EnTransito, self::RecibidaParcial], true);
    }

    public function abierta(): bool
    {
        return ! in_array($this, [self::Recibida, self::Cancelada], true);
    }
}
