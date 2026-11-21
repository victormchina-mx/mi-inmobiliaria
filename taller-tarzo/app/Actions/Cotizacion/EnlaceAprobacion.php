<?php

namespace App\Actions\Cotizacion;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\URL;

/**
 * Enlace firmado (72 h) para que el cliente o el ajustador autoricen la cotización sin cuenta.
 */
class EnlaceAprobacion
{
    public const HORAS_VIGENCIA = 72;

    public const ROLES = ['cliente', 'ajustador'];

    public static function generar(Cotizacion $cotizacion, string $rol = 'cliente'): string
    {
        $relativa = URL::temporarySignedRoute(
            'presupuesto.mostrar',
            now()->addHours(self::HORAS_VIGENCIA),
            ['cotizacion' => $cotizacion->id, 'rol' => $rol],
            absolute: false,
        );

        return url($relativa);
    }

    public static function telefono(Cotizacion $cotizacion, string $rol): ?string
    {
        if ($rol === 'ajustador') {
            $tel = preg_replace('/\D/', '', (string) $cotizacion->orden->siniestro?->ajustador_telefono);

            return $tel === '' ? null : (strlen($tel) === 10 ? '52'.$tel : $tel);
        }

        return $cotizacion->orden->cliente->numeroWhatsapp();
    }

    public static function whatsapp(Cotizacion $cotizacion, string $rol = 'cliente'): ?string
    {
        $numero = self::telefono($cotizacion, $rol);

        if (! $numero) {
            return null;
        }

        $orden = $cotizacion->orden;
        $saludo = $rol === 'ajustador'
            ? 'Buen día. Le compartimos la valuación del siniestro '.($orden->siniestro?->numero ?? $orden->numero_siniestro ?? '')
            : "Hola {$orden->cliente->nombre}, ya tenemos el presupuesto de tu {$orden->vehiculo->marca} {$orden->vehiculo->modelo}";

        $texto = sprintf(
            "%s (orden %s, total $%s).\n\nRevísalo y autoriza aquí: %s\n\nEl enlace vence en %d horas.",
            $saludo,
            $orden->folio,
            number_format($cotizacion->total, 2),
            self::generar($cotizacion, $rol),
            self::HORAS_VIGENCIA,
        );

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($texto);
    }
}
