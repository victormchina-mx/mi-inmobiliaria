<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Área de agentes ("Todas las propiedades", fichas técnicas): solo usuarios
 * con sesión de tipo agente o constructor. Va después de "user-auth".
 */
class AgentOnlyFilter implements FilterInterface
{
    public const TYPES = ['agent', 'builder'];

    public static function allowed(): bool
    {
        return session()->get('uid') && in_array(session()->get('utype'), self::TYPES, true);
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('uid')) {
            return redirect()->to('/login')->with('error', 'Debes iniciar sesión para continuar.');
        }
        if (! self::allowed()) {
            return redirect()->to('/profile')->with('error', 'Esta sección es solo para agentes inmobiliarios.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
