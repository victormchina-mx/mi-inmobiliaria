<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Testigos (luces de advertencia) encendidos en el tablero al ingresar.
 */
enum Testigo: string implements HasLabel
{
    case CheckEngine = 'check_engine';
    case Abs = 'abs';
    case BolsaAire = 'bolsa_aire';
    case PresionAceite = 'aceite';
    case Bateria = 'bateria';
    case Temperatura = 'temperatura';
    case Frenos = 'frenos';
    case PresionLlantas = 'presion_llantas';
    case Servicio = 'servicio';
    case ControlTraccion = 'control_traccion';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::CheckEngine => 'Check engine',
            self::Abs => 'ABS',
            self::BolsaAire => 'Bolsa de aire',
            self::PresionAceite => 'Presión de aceite',
            self::Bateria => 'Batería / carga',
            self::Temperatura => 'Temperatura',
            self::Frenos => 'Frenos',
            self::PresionLlantas => 'Presión de llantas',
            self::Servicio => 'Servicio requerido',
            self::ControlTraccion => 'Control de tracción',
            self::Otro => 'Otro',
        };
    }
}
