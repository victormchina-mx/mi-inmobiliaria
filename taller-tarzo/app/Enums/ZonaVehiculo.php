<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Zonas del vehículo para marcar daños previos en el check-in.
 * diagrama() devuelve [x, y, ancho, alto] en el SVG de vista superior (viewBox 0 0 200 400),
 * o null si la zona no se dibuja (se lista aparte).
 */
enum ZonaVehiculo: string implements HasLabel
{
    case FaciaDelantera = 'facia_delantera';
    case Cofre = 'cofre';
    case Parabrisas = 'parabrisas';
    case Toldo = 'toldo';
    case Medallon = 'medallon';
    case Cajuela = 'cajuela';
    case FaciaTrasera = 'facia_trasera';
    case SalpicaderaDelIzq = 'salpicadera_del_izq';
    case SalpicaderaDelDer = 'salpicadera_del_der';
    case PuertaDelIzq = 'puerta_del_izq';
    case PuertaDelDer = 'puerta_del_der';
    case PuertaTrasIzq = 'puerta_tras_izq';
    case PuertaTrasDer = 'puerta_tras_der';
    case CostadoTrasIzq = 'costado_tras_izq';
    case CostadoTrasDer = 'costado_tras_der';
    case EspejoIzq = 'espejo_izq';
    case EspejoDer = 'espejo_der';
    case Faros = 'faros';
    case Calaveras = 'calaveras';
    case Rines = 'rines';
    case Llantas = 'llantas';
    case CristalesLaterales = 'cristales_laterales';
    case Interior = 'interior';
    case Otra = 'otra';

    public function getLabel(): string
    {
        return match ($this) {
            self::FaciaDelantera => 'Facia delantera',
            self::Cofre => 'Cofre',
            self::Parabrisas => 'Parabrisas',
            self::Toldo => 'Toldo',
            self::Medallon => 'Medallón',
            self::Cajuela => 'Cajuela / tapa trasera',
            self::FaciaTrasera => 'Facia trasera',
            self::SalpicaderaDelIzq => 'Salpicadera delantera izq.',
            self::SalpicaderaDelDer => 'Salpicadera delantera der.',
            self::PuertaDelIzq => 'Puerta delantera izq.',
            self::PuertaDelDer => 'Puerta delantera der.',
            self::PuertaTrasIzq => 'Puerta trasera izq.',
            self::PuertaTrasDer => 'Puerta trasera der.',
            self::CostadoTrasIzq => 'Costado trasero izq.',
            self::CostadoTrasDer => 'Costado trasero der.',
            self::EspejoIzq => 'Espejo izq.',
            self::EspejoDer => 'Espejo der.',
            self::Faros => 'Faros',
            self::Calaveras => 'Calaveras',
            self::Rines => 'Rines',
            self::Llantas => 'Llantas',
            self::CristalesLaterales => 'Cristales laterales',
            self::Interior => 'Interior / vestiduras',
            self::Otra => 'Otra',
        };
    }

    /**
     * @return array{0:int,1:int,2:int,3:int}|null
     */
    public function diagrama(): ?array
    {
        return match ($this) {
            self::FaciaDelantera => [40, 6, 120, 26],
            self::Cofre => [45, 32, 110, 86],
            self::Parabrisas => [50, 118, 100, 30],
            self::Toldo => [50, 148, 100, 110],
            self::Medallon => [50, 258, 100, 26],
            self::Cajuela => [45, 284, 110, 82],
            self::FaciaTrasera => [40, 366, 120, 26],
            self::SalpicaderaDelIzq => [12, 32, 33, 86],
            self::SalpicaderaDelDer => [155, 32, 33, 86],
            self::PuertaDelIzq => [12, 118, 38, 76],
            self::PuertaDelDer => [150, 118, 38, 76],
            self::PuertaTrasIzq => [12, 194, 38, 76],
            self::PuertaTrasDer => [150, 194, 38, 76],
            self::CostadoTrasIzq => [12, 270, 33, 96],
            self::CostadoTrasDer => [155, 270, 33, 96],
            self::EspejoIzq => [0, 122, 12, 16],
            self::EspejoDer => [188, 122, 12, 16],
            default => null,
        };
    }
}
