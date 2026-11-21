<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Catálogo c_FormaPago del SAT (las usadas en un taller).
 */
enum FormaPago: string implements HasLabel
{
    case Efectivo = '01';
    case Cheque = '02';
    case Transferencia = '03';
    case TarjetaCredito = '04';
    case Condonacion = '15';
    case TarjetaDebito = '28';
    case PorDefinir = '99';

    public function getLabel(): string
    {
        return match ($this) {
            self::Efectivo => '01 · Efectivo',
            self::Cheque => '02 · Cheque',
            self::Transferencia => '03 · Transferencia',
            self::TarjetaCredito => '04 · Tarjeta de crédito',
            self::Condonacion => '15 · Condonación',
            self::TarjetaDebito => '28 · Tarjeta de débito',
            self::PorDefinir => '99 · Por definir',
        };
    }

    /** Formas válidas para registrar un pago real (no "por definir"). */
    public static function deCobro(): array
    {
        return collect(self::cases())->reject(fn ($c) => in_array($c, [self::PorDefinir, self::Condonacion], true))
            ->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])->all();
    }
}
