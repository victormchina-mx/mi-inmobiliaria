<?php

namespace App\Libraries;

/**
 * Enlaces de contacto con el agente de una propiedad: llamar, WhatsApp y
 * correo, con un mensaje ya escrito que menciona la propiedad.
 *
 * Los teléfonos se guardan como los escribe cada usuario ("7777444455",
 * "55 1234 5678", "+52 1 55..."): para WhatsApp se usa el formato
 * internacional sin "+" ni espacios; a un número mexicano de 10 dígitos se
 * le antepone 52.
 *
 * Vista: sección "Contactar al agente" de app/Views/property/detail.php
 */
class ContactLinks
{
    /** Solo los dígitos del teléfono ('' si no parece un teléfono). */
    public static function digits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : '';
    }

    /** Número para WhatsApp (formato internacional sin "+"), o '' si no hay. */
    public static function whatsappNumber(?string $phone): string
    {
        $digits = self::digits($phone);

        if ($digits === '') {
            return '';
        }

        // 044/045 + 10 dígitos o 01 + 10 dígitos (marcación antigua en México)
        if (preg_match('/^(?:04[45]|01)(\d{10})$/', $digits, $m)) {
            $digits = $m[1];
        }

        // Número nacional de México (10 dígitos) → 52 + número
        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        // 52 1 + 10 dígitos (formato antiguo de celulares) → 52 + 10 dígitos
        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return '52' . substr($digits, 3);
        }

        return $digits;
    }

    /** "tel:+527777444455" (o con el número tal cual si no es de 10 dígitos). */
    public static function tel(?string $phone): string
    {
        $wa = self::whatsappNumber($phone);

        return $wa === '' ? '' : 'tel:+' . $wa;
    }

    /** Teléfono legible: "777 744 4455" para 10 dígitos; si no, como se guardó. */
    public static function pretty(?string $phone): string
    {
        $digits = self::digits($phone);

        if (strlen($digits) === 10) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6);
        }

        return trim((string) $phone);
    }

    /** Mensaje para el agente, con el nombre y el enlace de la propiedad. */
    public static function message(string $title, string $url): string
    {
        return 'Hola, me interesa la propiedad "' . trim($title) . '" que vi en ' . $url . '. ¿Sigue disponible?';
    }

    /** https://wa.me/52…?text=… o '' si no hay teléfono. */
    public static function whatsapp(?string $phone, string $title, string $url): string
    {
        $number = self::whatsappNumber($phone);

        return $number === '' ? '' : 'https://wa.me/' . $number . '?text=' . rawurlencode(self::message($title, $url));
    }

    /** mailto: con asunto y mensaje, o '' si el correo no es válido. */
    public static function mail(?string $email, string $title, string $url): string
    {
        $email = trim((string) $email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return 'mailto:' . $email
            . '?subject=' . rawurlencode('Interés en "' . trim($title) . '"')
            . '&body=' . rawurlencode(self::message($title, $url));
    }
}
