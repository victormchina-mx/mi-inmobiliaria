<?php

namespace App\Libraries;

/**
 * Tokens firmados con HMAC-SHA256 usando sólo funciones nativas de PHP,
 * para no depender de ninguna librería vía Composer en el hosting.
 */
class SimpleJwt
{
    /** Longitud mínima de JWT_SECRET (32 bytes = 256 bits, lo que pide HS256). */
    private const MIN_SECRET_LENGTH = 32;

    /**
     * Clave de firma. Si falta o es demasiado corta se devuelve null y no se
     * emite ni se acepta ningún token: con una clave vacía cualquiera podría
     * firmar tokens válidos para cualquier usuario.
     */
    private static function secret(): ?string
    {
        $secret = (string) env('JWT_SECRET');

        if (strlen($secret) < self::MIN_SECRET_LENGTH) {
            log_message('critical', 'JWT_SECRET no está definido o tiene menos de ' . self::MIN_SECRET_LENGTH . ' caracteres; la API rechaza todos los tokens.');

            return null;
        }

        return $secret;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    /** Genera un token firmado. $payload debe incluir al menos 'uid'. */
    public static function encode(array $payload, int $ttlSeconds = 2592000): string
    {
        $secret = self::secret();
        if ($secret === null) {
            throw new \RuntimeException('JWT_SECRET no está configurado correctamente.');
        }

        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $header    = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body      = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, $secret, true));

        return $header . '.' . $body . '.' . $signature;
    }

    /** Verifica firma y expiración; devuelve el payload o null si no es válido. */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        $secret = self::secret();
        if ($secret === null) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        // Solo se acepta el algoritmo con el que se emiten los tokens
        $head = json_decode(self::base64UrlDecode($header), true);
        if (! is_array($head) || ($head['alg'] ?? null) !== 'HS256') {
            return null;
        }

        $expected = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, $secret, true));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($body), true);

        if (! is_array($payload) || ! isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}
