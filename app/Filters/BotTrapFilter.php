<?php

namespace App\Filters;

use App\Libraries\BotTrap;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rechaza envíos de robots en formularios públicos (App\Libraries\BotTrap).
 *
 * Uso en Routes.php:  ['filter' => 'bottrap:3']   (3 = segundos mínimos)
 *
 * - Campo trampa lleno: se responde como si todo hubiera salido bien, para
 *   que el robot no aprenda a evitarlo, pero no se hace nada.
 * - Muy rápido o sin hora válida: se regresa al formulario con un aviso
 *   (una persona que se equivoca puede volver a enviarlo).
 */
class BotTrapFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return null;
        }

        $min    = max(1, (int) ($arguments[0] ?? 3));
        $result = BotTrap::check((array) $request->getPost(), $min);

        if ($result === 'ok') {
            return null;
        }

        log_message('notice', 'BotTrap: {result} en {path} desde {ip}', [
            'result' => $result,
            'path'   => $request->getUri()->getPath(),
            'ip'     => $request->getIPAddress(),
        ]);

        if ($result === 'honeypot') {
            return redirect()->back()->with('success', 'Gracias, recibimos tu solicitud.');
        }

        return redirect()->back()->withInput()
            ->with('error', 'Por seguridad, espera unos segundos y vuelve a enviar el formulario.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
