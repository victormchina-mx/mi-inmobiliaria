<?php

namespace App\Libraries;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;

/**
 * Sesiones de la app móvil (tokens Bearer).
 *
 * Además de la firma y la expiración que revisa SimpleJwt, cada token:
 *  - queda ligado a la contraseña del usuario ("pwd"): si la contraseña
 *    cambia (admin o "restablecer contraseña"), todos sus tokens dejan de
 *    valer, y si el usuario se borra, también;
 *  - vence tras 1 hora sin uso (TOKEN_TTL); cada petición autenticada lo
 *    renueva, conservando cuándo se inició sesión ("auth_time"), y nunca
 *    más allá de MAX_SESSION desde el login;
 *  - tiene un identificador ("jti") que se puede revocar al cerrar sesión.
 *
 * Los tokens anteriores a este cambio no tienen estos datos y se rechazan:
 * los usuarios inician sesión de nuevo una vez.
 */
class ApiAuth
{
    /**
     * Vigencia de cada token: 1 hora SIN USO. Cada petición autenticada
     * devuelve un token nuevo en la cabecera X-Auth-Token (ApiAuthFilter),
     * así que mientras la app se use la sesión sigue; tras 1 hora sin
     * actividad el token vence y la app pide correo y contraseña.
     */
    public const TOKEN_TTL = HOUR;

    /**
     * Límite absoluto desde el inicio de sesión, aunque la app se use a
     * diario: la renovación conserva auth_time y no pasa de este tiempo.
     */
    public const MAX_SESSION = 90 * DAY;

    /** Cabecera en la que se entrega el token renovado. */
    public const REFRESH_HEADER = 'X-Auth-Token';

    private const REVOKED_PREFIX = 'api_revoked_';

    /** @var array<string, array{user: array, payload: array}|null> */
    private static array $cache = [];

    /** Emite un token para $user. Lanza RuntimeException si JWT_SECRET no es válido. */
    public static function issue(array $user, ?int $authTime = null): string
    {
        return SimpleJwt::encode([
            'uid'       => (int) $user['uid'],
            'pwd'       => self::passwordFingerprint((string) $user['upass']),
            'auth_time' => $authTime ?? time(),
            'jti'       => bin2hex(random_bytes(16)),
        ], self::TOKEN_TTL);
    }

    /**
     * Valida el token Bearer de la petición.
     *
     * @return array{user: array, payload: array}|null
     */
    public static function fromRequest(RequestInterface $request): ?array
    {
        if (! preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $m)) {
            return null;
        }

        $token = $m[1];

        if (! array_key_exists($token, self::$cache)) {
            self::$cache[$token] = self::authenticate($token);
        }

        return self::$cache[$token];
    }

    /** Cierra la sesión de este token: deja de aceptarse hasta que habría expirado. */
    public static function revoke(array $payload): void
    {
        $ttl = max(1, (int) $payload['exp'] - time());
        cache()->save(self::REVOKED_PREFIX . $payload['jti'], 1, $ttl);
        self::$cache = [];
    }

    /** @return array{user: array, payload: array}|null */
    private static function authenticate(string $token): ?array
    {
        $payload = SimpleJwt::decode($token);

        if ($payload === null) {
            return null;
        }

        foreach (['uid', 'pwd', 'auth_time', 'jti', 'exp'] as $claim) {
            if (! isset($payload[$claim])) {
                return null; // token anterior a este cambio o incompleto
            }
        }

        if ((int) $payload['auth_time'] + self::MAX_SESSION < time()) {
            return null; // sesión demasiado vieja: hay que iniciar sesión otra vez
        }

        if (! is_string($payload['jti']) || ! ctype_xdigit($payload['jti'])
            || cache(self::REVOKED_PREFIX . $payload['jti']) !== null) {
            return null; // sesión cerrada con /auth/logout
        }

        $user = (new UserModel())->find((int) $payload['uid']);

        if (! $user || ! hash_equals(self::passwordFingerprint((string) $user['upass']), (string) $payload['pwd'])) {
            return null; // usuario borrado o contraseña cambiada
        }

        return ['user' => $user, 'payload' => $payload];
    }

    /**
     * Huella de la contraseña guardada (hash bcrypt), firmada con JWT_SECRET
     * para no exponer nada del hash en el token, que cualquiera puede leer.
     */
    private static function passwordFingerprint(string $passwordHash): string
    {
        return substr(hash_hmac('sha256', 'api-session|' . $passwordHash, (string) env('JWT_SECRET')), 0, 32);
    }
}
