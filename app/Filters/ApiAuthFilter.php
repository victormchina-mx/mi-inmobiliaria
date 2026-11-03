<?php

namespace App\Filters;

use App\Libraries\SimpleJwt;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Token no proporcionado.']);
        }

        if (! SimpleJwt::decode($matches[1])) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Token inválido o expirado.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
