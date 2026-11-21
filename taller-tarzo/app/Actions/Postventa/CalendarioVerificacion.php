<?php

namespace App\Actions\Postventa;

/**
 * Calendario de verificación vehicular del Estado de México y la Ciudad de México
 * según el último dígito de la placa (dos periodos al año). Revisar si la autoridad lo cambia.
 */
final class CalendarioVerificacion
{
    /** dígito => [engomado, mes inicio 1er periodo, mes inicio 2º periodo] (cada periodo dura dos meses) */
    public const CALENDARIO = [
        5 => ['amarillo', 1, 7], 6 => ['amarillo', 1, 7],
        7 => ['rosa', 2, 8], 8 => ['rosa', 2, 8],
        3 => ['rojo', 3, 9], 4 => ['rojo', 3, 9],
        1 => ['verde', 4, 10], 2 => ['verde', 4, 10],
        9 => ['azul', 5, 11], 0 => ['azul', 5, 11],
    ];

    public static function digito(?string $placas): ?int
    {
        if (! preg_match_all('/\d/', (string) $placas, $m)) {
            return null;
        }

        return (int) end($m[0]);
    }

    /**
     * @return array{engomado: string, periodo: int, inicio: int, fin: int}|null si la placa verifica a partir de ese mes
     */
    public static function periodoQueIniciaEn(?string $placas, int $mes): ?array
    {
        $d = self::digito($placas);
        if ($d === null) {
            return null;
        }

        [$engomado, $p1, $p2] = self::CALENDARIO[$d];

        return match ($mes) {
            $p1 => ['engomado' => $engomado, 'periodo' => 1, 'inicio' => $p1, 'fin' => $p1 + 1],
            $p2 => ['engomado' => $engomado, 'periodo' => 2, 'inicio' => $p2, 'fin' => $p2 + 1],
            default => null,
        };
    }
}
