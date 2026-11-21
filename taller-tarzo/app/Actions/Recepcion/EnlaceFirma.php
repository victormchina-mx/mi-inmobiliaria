<?php

namespace App\Actions\Recepcion;

use App\Models\Checkin;
use Illuminate\Support\Facades\URL;

/**
 * Enlace firmado (caduca en 72 h) para que el cliente firme el inventario desde su celular.
 */
class EnlaceFirma
{
    public const HORAS_VIGENCIA = 72;

    public static function generar(Checkin $checkin): string
    {
        // Firma relativa: el enlace sigue siendo válido aunque el cliente entre por http/https
        // o por otro dominio del mismo servidor (CDN de Hostinger, cambio de dominio al VPS).
        $relativa = URL::temporarySignedRoute('firma.mostrar', now()->addHours(self::HORAS_VIGENCIA), ['checkin' => $checkin->id], absolute: false);

        return url($relativa);
    }

    /**
     * Enlace de WhatsApp con el mensaje listo (mientras se integra la API oficial en el Módulo 5).
     */
    public static function whatsapp(Checkin $checkin): ?string
    {
        $numero = $checkin->orden->cliente->numeroWhatsapp();

        if (! $numero) {
            return null;
        }

        $vehiculo = $checkin->orden->vehiculo;
        $texto = sprintf(
            "Hola %s, recibimos tu %s %s (orden %s). Revisa y firma el inventario de ingreso aquí: %s\n\nEl enlace vence en %d horas.",
            $checkin->orden->cliente->nombre,
            $vehiculo->marca,
            $vehiculo->modelo,
            $checkin->orden->folio,
            self::generar($checkin),
            self::HORAS_VIGENCIA,
        );

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($texto);
    }
}
