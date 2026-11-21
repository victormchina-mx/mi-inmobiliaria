<?php

namespace App\Support;

/**
 * Número de identificación vehicular (NIV / VIN, ISO 3779).
 *
 * El formato (17 caracteres, sin I, O ni Q) es obligatorio. El dígito verificador
 * (posición 9) solo es obligatorio en vehículos para el mercado de Norteamérica;
 * algunos importados no lo cumplen, por eso se reporta como advertencia.
 */
final class Vin
{
    private const PESOS = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

    private const VALORES = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
        'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
        'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9,
    ];

    public static function normalizar(string $vin): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $vin));
    }

    public static function formatoValido(string $vin): bool
    {
        return (bool) preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', self::normalizar($vin));
    }

    public static function digitoVerificadorValido(string $vin): bool
    {
        $vin = self::normalizar($vin);

        if (! self::formatoValido($vin)) {
            return false;
        }

        $suma = 0;
        foreach (str_split($vin) as $i => $c) {
            $valor = ctype_digit($c) ? (int) $c : self::VALORES[$c];
            $suma += $valor * self::PESOS[$i];
        }

        $resto = $suma % 11;

        return $vin[8] === ($resto === 10 ? 'X' : (string) $resto);
    }
}
