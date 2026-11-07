<?php

namespace App\Filters;

use App\Libraries\ApiAuth;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Exige un token Bearer válido (ver App\Libraries\ApiAuth): firma, vigencia,
 * sesión no cerrada, usuario existente y contraseña sin cambios.
 */
class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! preg_match('/^Bearer\s+\S+$/i', $request->getHeaderLine('Authorization'))) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Token no proporcionado.']);
        }

        if (ApiAuth::fromRequest($request) === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Tu sesión expiró o se cerró. Inicia sesión de nuevo.']);
        }
    }

    /**
     * Renueva la sesión en cada petición correcta: devuelve un token nuevo
     * (válido 1 hora más) en la cabecera X-Auth-Token. Tras /auth/logout el
     * token ya está revocado y no se entrega ninguno.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($response->getStatusCode() >= 400 || $response->hasHeader(ApiAuth::REFRESH_HEADER)) {
            return $response;
        }

        $auth = ApiAuth::fromRequest($request);

        if ($auth !== null) {
            try {
                $response->setHeader(
                    ApiAuth::REFRESH_HEADER,
                    ApiAuth::issue($auth['user'], (int) $auth['payload']['auth_time'])
                );
            } catch (\RuntimeException $e) {
                // JWT_SECRET mal configurado: se responde sin renovar.
            }
        }

        return $response;
    }
}
