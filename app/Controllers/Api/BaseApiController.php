<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\SimpleJwt;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends BaseController
{
    /** Uid del usuario autenticado según el Bearer token (null si no hay/no es válido). */
    protected function currentUid(): ?int
    {
        $header = $this->request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $payload = SimpleJwt::decode($matches[1]);

        return isset($payload['uid']) ? (int) $payload['uid'] : null;
    }

    /** Cuerpo de la petición: JSON si Content-Type lo indica, si no los campos POST normales. */
    protected function body(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : (array) $this->request->getPost();
    }

    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($data);
    }
}
