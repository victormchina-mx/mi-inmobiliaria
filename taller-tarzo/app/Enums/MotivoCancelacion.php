<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Motivos de cancelación del SAT (CFDI 4.0).
 */
enum MotivoCancelacion: string implements HasLabel
{
    case ConErrorConRelacion = '01';
    case ConErrorSinRelacion = '02';
    case NoSeLlevoACabo = '03';
    case NominativaGlobal = '04';

    public function getLabel(): string
    {
        return match ($this) {
            self::ConErrorConRelacion => '01 · Con errores, con relación (ya se emitió la que la sustituye)',
            self::ConErrorSinRelacion => '02 · Con errores, sin relación',
            self::NoSeLlevoACabo => '03 · No se llevó a cabo la operación',
            self::NominativaGlobal => '04 · Operación nominativa relacionada en factura global',
        };
    }
}
