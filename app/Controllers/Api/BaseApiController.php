<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\ApiAuth;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends BaseController
{
    /** Uid del usuario autenticado según el Bearer token (null si no hay/no es válido). */
    protected function currentUid(): ?int
    {
        $auth = ApiAuth::fromRequest($this->request);

        return $auth === null ? null : (int) $auth['user']['uid'];
    }

    /**
     * Usuario y datos del token de la petición actual.
     *
     * @return array{user: array, payload: array}|null
     */
    protected function currentAuth(): ?array
    {
        return ApiAuth::fromRequest($this->request);
    }

    /** Cuerpo de la petición: JSON si Content-Type lo indica, si no los campos POST normales. */
    protected function body(): array
    {
        // getJSON() lanza una excepción si el cuerpo no es JSON (p. ej. un
        // formulario x-www-form-urlencoded); solo se usa cuando el
        // Content-Type lo indica, y un JSON mal formado se trata como vacío.
        if (stripos($this->request->getHeaderLine('Content-Type'), 'json') !== false) {
            try {
                $json = $this->request->getJSON(true);
            } catch (\Throwable $e) {
                $json = null;
            }

            return is_array($json) ? $json : [];
        }

        return (array) $this->request->getPost();
    }

    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($data);
    }
}
