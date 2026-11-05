<?php

namespace App\Controllers\Api;

use App\Libraries\SimpleJwt;
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

        if (! $this->validate($rules)) {
            return $this->jsonResponse(['error' => implode(' ', $this->validator->getErrors())], 422);
        }

        $body = $this->body();

        $userModel = new UserModel();
        $user      = $userModel->where('uemail', $body['email'])->first();

        if (! $user || ! password_verify((string) $body['pass'], $user['upass'])) {
            return $this->jsonResponse(['error' => 'Email o contraseña incorrectos.'], 401);
        }

        return $this->tokenResponse($user);
    }

    public function me()
    {
        $uid = $this->currentUid();

        if (! $uid) {
            return $this->jsonResponse(['error' => 'Token inválido o expirado.'], 401);
        }

        $user = (new UserModel())->find($uid);

        if (! $user) {
            return $this->jsonResponse(['error' => 'Usuario no encontrado.'], 404);
        }

        return $this->tokenResponse($user);
    }

    /** Emite un token nuevo; si JWT_SECRET no está bien configurado responde 503 en vez de un error 500. */
    private function tokenResponse(array $user)
    {
        try {
            $token = SimpleJwt::encode(['uid' => (int) $user['uid']]);
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
