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

    /**
     * POST /me/photo (multipart "photo"): nueva foto de perfil desde la app
     * (cámara o galería, ya recortada en el círculo). Reemplaza la anterior.
     */
    public function photo()
    {
        $auth = $this->currentAuth();
        if (! $auth) {
            return $this->jsonResponse(['error' => 'Tu sesión expiró o se cerró. Inicia sesión de nuevo.'], 401);
        }
        $user = $auth['user'];

        $file = $this->request->getFile('photo');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->jsonResponse(['error' => 'No se recibió la foto. Intenta de nuevo.'], 422);
        }
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true) || ! @getimagesize($file->getTempName())) {
            return $this->jsonResponse(['error' => 'La foto debe ser JPG, PNG o WebP.'], 422);
        }
        if ($file->getSize() > 8 * 1024 * 1024) {
            return $this->jsonResponse(['error' => 'La foto es demasiado grande (máximo 8 MB).'], 422);
        }

        $name = $file->getRandomName();
        $file->move(FCPATH . 'uploads/user', $name);

        (new UserModel())->skipValidation(true)->update((int) $user['uid'], ['uimage' => $name]);
        self::deleteUserPhoto($user['uimage'] ?? null);

        $user['uimage'] = $name;

        return $this->jsonResponse(['user' => $this->publicUser($user), 'message' => 'Foto actualizada.']);
    }

    /** Borra la foto anterior y sus copias reducidas (public/img/user/*). */
    private static function deleteUserPhoto(?string $file): void
    {
        if (! $file || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file)) {
            return;
        }
        $paths = [FCPATH . 'uploads/user/' . $file];
        foreach (\App\Controllers\Thumb::WIDTHS as $w) {
            $paths[] = FCPATH . 'img/user/' . $w . '/' . $file . '.webp';
            $paths[] = FCPATH . 'img/user/' . $w . '/' . $file . '.jpg';
        }
        foreach ($paths as $p) {
            if (is_file($p)) {
                @unlink($p);
            }
        }
    }

    private function publicUser(array $user): array
    {
        return [
            'uid'    => (int) $user['uid'],
            'uemail' => $user['uemail'],
            'uname'  => $user['uname'],
            'utype'  => $user['utype'],
            // Foto de perfil (copia reducida) o null
            'photo'  => ! empty($user['uimage']) ? thumb_url('user', $user['uimage'], 400) : null,
        ];
    }
}
