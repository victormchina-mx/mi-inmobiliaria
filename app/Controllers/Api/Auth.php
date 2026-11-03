<?php

namespace App\Controllers\Api;

use App\Libraries\SimpleJwt;
use App\Models\UserModel;

class Auth extends BaseApiController
{
    public function login()
    {
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

        return $this->jsonResponse([
            'token' => SimpleJwt::encode(['uid' => (int) $user['uid']]),
            'user'  => $this->publicUser($user),
        ]);
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

        return $this->jsonResponse([
            'token' => SimpleJwt::encode(['uid' => (int) $user['uid']]),
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
