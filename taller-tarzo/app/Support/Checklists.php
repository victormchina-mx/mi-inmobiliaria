<?php

namespace App\Support;

/**
 * Puntos de verificación de control de calidad y de entrega.
 */
final class Checklists
{
    public const CALIDAD = [
        'prueba_ruta' => 'Prueba de ruta sin ruidos ni vibraciones',
        'testigos' => 'Testigos del tablero apagados',
        'niveles' => 'Niveles de fluidos correctos',
        'tono' => 'Tono de pintura igualado con las piezas vecinas',
        'acabado' => 'Pintura sin grumos, escurrimientos ni polvo',
        'claros' => 'Claros y ajuste de piezas parejos',
        'funciones' => 'Luces, vidrios, seguros y accesorios funcionan',
        'apriete' => 'Birlos y tornillería con apriete correcto',
        'limpieza' => 'Vehículo limpio por dentro y por fuera',
        'piezas_retiradas' => 'Piezas retiradas separadas para entregar o devolver',
    ];

    public const ENTREGA = [
        'explicacion' => 'Se explicó al cliente el trabajo realizado',
        'piezas' => 'Se mostraron o entregaron las piezas cambiadas',
        'pertenencias' => 'Pertenencias e inventario de ingreso completos',
        'condiciones' => 'El cliente revisó el vehículo y está conforme',
        'garantia' => 'Se informó la garantía del trabajo',
        'pago' => 'Pago o crédito confirmado',
    ];

    /**
     * @return array<string, string>
     */
    public static function puntos(string $tipo): array
    {
        return $tipo === 'entrega' ? self::ENTREGA : self::CALIDAD;
    }
}
