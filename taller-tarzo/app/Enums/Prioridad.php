<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Semáforo de prioridad de un hallazgo de diagnóstico.
 */
enum Prioridad: string implements HasColor, HasLabel
{
    case Urgente = 'urgente';
    case Recomendado = 'recomendado';
    case PuedeEsperar = 'puede_esperar';

    public function getLabel(): string
    {
        return match ($this) {
            self::Urgente => 'Urgente',
            self::Recomendado => 'Recomendado',
            self::PuedeEsperar => 'Puede esperar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Urgente => 'danger',
            self::Recomendado => 'warning',
            self::PuedeEsperar => 'success',
        };
    }
}
