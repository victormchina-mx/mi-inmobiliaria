<?php

namespace App\Libraries;

/**
 * Tokens firmados con HMAC-SHA256 usando sólo funciones nativas de PHP,
 * para no depender de ninguna librería vía Composer en el hosting.
 */
class SimpleJwt
{
    private static function secret(): string
    {
        return (string) env('JWT_SECRET');
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
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $header    = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body      = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, self::secret(), true));

        return $header . '.' . $body . '.' . $signature;
    }

    /** Verifica firma y expiración; devuelve el payload o null si no es válido. */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        $expected = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, self::secret(), true));

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
