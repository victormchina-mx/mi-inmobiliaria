<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Elementos que se verifican al recibir el vehículo.
 */
enum ElementoInventario: string implements HasLabel
{
    case LlantaRefaccion = 'llanta_refaccion';
    case Gato = 'gato';
    case LlaveBirlos = 'llave_birlos';
    case Herramienta = 'herramienta';
    case Triangulos = 'triangulos';
    case Extintor = 'extintor';
    case CablesCorriente = 'cables_corriente';
    case Tapetes = 'tapetes';
    case Estereo = 'estereo';
    case Antena = 'antena';
    case TaponGasolina = 'tapon_gasolina';
    case TaponesRin = 'tapones_rin';
    case BirloSeguridad = 'birlo_seguridad';
    case TarjetaCirculacion = 'tarjeta_circulacion';
    case PolizaSeguro = 'poliza_seguro';
    case Manual = 'manual';
    case LlaveExtra = 'llave_extra';
    case ControlAlarma = 'control_alarma';

    public function getLabel(): string
    {
        return match ($this) {
            self::LlantaRefaccion => 'Llanta de refacción',
            self::Gato => 'Gato',
            self::LlaveBirlos => 'Llave de birlos',
            self::Herramienta => 'Herramienta',
            self::Triangulos => 'Triángulos',
            self::Extintor => 'Extintor',
            self::CablesCorriente => 'Cables pasa corriente',
            self::Tapetes => 'Tapetes',
            self::Estereo => 'Estéreo / pantalla',
            self::Antena => 'Antena',
            self::TaponGasolina => 'Tapón de gasolina',
            self::TaponesRin => 'Tapones de rin',
            self::BirloSeguridad => 'Birlo de seguridad',
            self::TarjetaCirculacion => 'Tarjeta de circulación',
            self::PolizaSeguro => 'Póliza de seguro',
            self::Manual => 'Manual del propietario',
            self::LlaveExtra => 'Llave de repuesto',
            self::ControlAlarma => 'Control de alarma',
        };
    }
}
