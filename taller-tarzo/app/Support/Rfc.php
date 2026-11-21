<?php

namespace App\Support;

/**
 * Validación de RFC mexicano (formato y fecha). No consulta al SAT.
 */
final class Rfc
{
    public const GENERICO_NACIONAL = 'XAXX010101000';

    public const GENERICO_EXTRANJERO = 'XEXX010101000';

    public static function normalizar(string $rfc): string
    {
        return mb_strtoupper(preg_replace('/[\s\-]/u', '', $rfc));
    }

    public static function esValido(string $rfc): bool
    {
        $rfc = self::normalizar($rfc);

        if (in_array($rfc, [self::GENERICO_NACIONAL, self::GENERICO_EXTRANJERO], true)) {
            return true;
        }

        if (! preg_match('/^([A-ZÑ&]{3,4})(\d{2})(\d{2})(\d{2})([A-Z\d]{3})$/u', $rfc, $m)) {
            return false;
        }

        return checkdate((int) $m[3], (int) $m[4], 2000 + (int) $m[2]);
    }

    public static function esPersonaMoral(string $rfc): bool
    {
        return mb_strlen(self::normalizar($rfc)) === 12;
    }
}
