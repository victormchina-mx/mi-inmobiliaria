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

    /** Tipo leído de la BD en esta petición (uid => utype). */
    private static array $checked = [];

    /**
     * El tipo se toma de la BD (no solo de la sesión): si el admin cambia a
     * alguien de "Usuario" a "Agente", el menú y el acceso se actualizan sin
     * que tenga que cerrar sesión. La sesión se sincroniza con el valor real.
     */
    public static function allowed(): bool
    {
        $uid = (int) session()->get('uid');
        if (! $uid) {
            return false;
        }

        if (! array_key_exists($uid, self::$checked)) {
            $type = session()->get('utype');
            try {
                $row = db_connect()->table('user')->select('utype')->where('uid', $uid)->get()->getRowArray();
                if ($row) {
                    $type = (string) $row['utype'];
                    if ($type !== session()->get('utype')) {
                        session()->set('utype', $type);
                    }
                }
            } catch (\Throwable) {
                // Sin BD disponible: se usa lo que hay en la sesión
            }
            self::$checked[$uid] = $type;
        }

        return in_array(self::$checked[$uid], self::TYPES, true);
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
