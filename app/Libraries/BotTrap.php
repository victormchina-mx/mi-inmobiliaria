<?php

namespace App\Libraries;

/**
 * Defensa invisible contra robots en los formularios públicos (Contacto,
 * Registro y "Olvidé mi contraseña"), sin "no soy un robot":
 *
 *  1. Campo trampa (honeypot): un campo "website" que las personas no ven
 *     ni pueden enfocar; los robots que llenan todo lo llenan.
 *  2. Tiempo mínimo: el formulario lleva la hora en que se mostró, firmada
 *     con JWT_SECRET para que no se pueda falsificar; si se envía en menos
 *     de MIN_SECONDS, lo envió un robot (una persona tarda más).
 *
 * Vista: partials/bot_trap.php · Filtro: App\Filters\BotTrapFilter
 */
class BotTrap
{
    public const FIELD      = 'website';    // campo trampa
    public const TIME_FIELD = 'form_token'; // hora firmada
    public const MAX_AGE    = DAY;          // un formulario de hace más de un día se vuelve a mostrar

    /** Hora actual firmada: "1731000000.ab12…". */
    public static function token(?int $time = null): string
    {
        $time ??= time();

        return $time . '.' . self::sign($time);
    }

    /**
     * Revisa lo enviado.
     *
     * @return string 'ok' | 'honeypot' (robot: se descarta en silencio) | 'fast' | 'invalid'
     */
    public static function check(array $post, int $minSeconds, ?int $now = null): string
    {
        $now ??= time();

        if (trim((string) ($post[self::FIELD] ?? '')) !== '') {
            return 'honeypot';
        }

        $token = (string) ($post[self::TIME_FIELD] ?? '');
        if (! preg_match('/^(\d{9,11})\.([a-f0-9]{32})$/', $token, $m) || ! hash_equals(self::sign((int) $m[1]), $m[2])) {
            return self::key() === '' ? 'ok' : 'invalid'; // sin clave en el servidor no se puede revisar la hora
        }

        $elapsed = $now - (int) $m[1];

        if ($elapsed < $minSeconds) {
            return 'fast';
        }

        return $elapsed > self::MAX_AGE ? 'invalid' : 'ok';
    }

    private static function sign(int $time): string
    {
        return substr(hash_hmac('sha256', 'bot-trap|' . $time, self::key()), 0, 32);
    }

    private static function key(): string
    {
        return (string) env('JWT_SECRET', '');
    }
}
