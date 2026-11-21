<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Avance del siniestro con la aseguradora.
 */
enum EstatusSiniestro: string implements HasColor, HasLabel
{
    case PorValuar = 'por_valuar';
    case ValuacionEnviada = 'valuacion_enviada';
    case Autorizado = 'autorizado';
    case Rechazado = 'rechazado';
    case PerdidaTotal = 'perdida_total';

    public function getLabel(): string
    {
        return match ($this) {
            self::PorValuar => 'Por valuar',
            self::ValuacionEnviada => 'Valuación enviada',
            self::Autorizado => 'Autorizado',
            self::Rechazado => 'Rechazado',
            self::PerdidaTotal => 'Pérdida total',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PorValuar => 'gray',
            self::ValuacionEnviada => 'info',
            self::Autorizado => 'success',
            self::Rechazado => 'danger',
            self::PerdidaTotal => 'danger',
        };
    }
}
