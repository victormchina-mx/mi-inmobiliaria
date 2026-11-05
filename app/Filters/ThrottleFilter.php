<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Limita cuántas veces puede enviarse un formulario por IP, para frenar
 * los intentos de adivinar contraseñas.
 *
 * Uso en Routes.php:  ['filter' => 'throttle:5,60']  (5 intentos por minuto)
 * Argumentos: intentos permitidos, ventana en segundos.
 *
 * Cada ruta lleva su propio contador. Los contadores se guardan en la
 * caché (writable/cache). Al pasarse, regresa al formulario con un mensaje
 * en "error" (los formularios ya lo muestran). No se usa withInput() para
 * no guardar la contraseña en la sesión.
 */
class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $capacity = max(1, (int) ($arguments[0] ?? 5));
        $seconds  = max(1, (int) ($arguments[1] ?? MINUTE));

        $throttler = service('throttler');

        // Los segmentos largos (p. ej. el hash de reset-password/{hash}) se
        // agrupan: si no, cada hash probado tendría su propio contador.
        $path   = preg_replace('#/[A-Za-z0-9_-]{16,}#', '/*', $request->getUri()->getPath());
        $bucket = 'web-' . md5($path . '|' . $request->getIPAddress());

        if ($throttler->check($bucket, $capacity, $seconds) === false) {
            $wait    = max(1, $throttler->getTokenTime());
            $minutes = (int) ceil($wait / 60);
            $message = 'Demasiados intentos. Espera '
                . ($wait < 60 ? $wait . ' segundos' : $minutes . ($minutes === 1 ? ' minuto' : ' minutos'))
                . ' e inténtalo de nuevo.';

            return redirect()->back()->with('error', $message);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
