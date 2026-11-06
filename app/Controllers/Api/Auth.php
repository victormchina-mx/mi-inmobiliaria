<?php

namespace App\Controllers\Api;

use App\Libraries\ApiAuth;
use App\Models\UserModel;

class Auth extends BaseApiController
{
    /** Intentos de login permitidos por IP en un minuto. */
    private const LOGIN_ATTEMPTS_PER_MINUTE = 5;

    public function login()
    {
        // Frena los intentos de adivinar contraseñas (por IP)
        $throttler = service('throttler');
        $bucket    = 'api-login-' . md5((string) $this->request->getIPAddress());

        if ($throttler->check($bucket, self::LOGIN_ATTEMPTS_PER_MINUTE, MINUTE) === false) {
            return $this->jsonResponse(['error' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'], 429)
                ->setHeader('Retry-After', (string) max(1, $throttler->getTokenTime()));
        }

        $rules = [
            'email' => 'required|valid_email',
            'pass'  => 'required',
        ];

        // validateData() sobre body(): validate() leería el JSON por su cuenta
        // y lanzaría una excepción (500) si viene mal formado.
        $body = $this->body();

        if (! $this->validateData($body, $rules)) {
            return $this->jsonResponse(['error' => implode(' ', $this->validator->getErrors())], 422);
        }

        $userModel = new UserModel();
        $user      = $userModel->where('uemail', $body['email'])->first();

        if (! $user || ! password_verify((string) $body['pass'], $user['upass'])) {
            return $this->jsonResponse(['error' => 'Email o contraseña incorrectos.'], 401);
        }

        return $this->tokenResponse($user);
    }

    /**
     * Datos del usuario y token renovado. La renovación conserva la fecha del
     * inicio de sesión, así que la sesión nunca dura más de ApiAuth::MAX_SESSION.
     */
    public function me()
    {
        $auth = $this->currentAuth();

        if ($auth === null) {
            return $this->jsonResponse(['error' => 'Tu sesión expiró o se cerró. Inicia sesión de nuevo.'], 401);
        }

        return $this->tokenResponse($auth['user'], (int) $auth['payload']['auth_time']);
    }

    /** Cierra la sesión del token actual en el servidor (deja de aceptarse). */
    public function logout()
    {
        $auth = $this->currentAuth();

        if ($auth !== null) {
            ApiAuth::revoke($auth['payload']);
        }

        return $this->jsonResponse(['message' => 'Sesión cerrada.']);
    }

    /** Emite un token nuevo; si JWT_SECRET no está bien configurado responde 503 en vez de un error 500. */
    private function tokenResponse(array $user, ?int $authTime = null)
    {
        try {
            $token = ApiAuth::issue($user, $authTime);
        } catch (\RuntimeException $e) {
            return $this->jsonResponse(['error' => 'El servicio no está disponible. Inténtalo más tarde.'], 503);
        }

        return $this->jsonResponse([
            'token' => $token,
            'user'  => $this->publicUser($user),
        ]);
    }

    private function publicUser(array $user): array
    {
        return [
            'uid'    => (int) $user['uid'],
            'uemail' => $user['uemail'],
            'uname'  => $user['uname'],
            'utype'  => $user['utype'],
        ];
    }
}
