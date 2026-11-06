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

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
