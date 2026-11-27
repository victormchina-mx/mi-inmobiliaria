<?php

namespace App\Http\Controllers;

use App\Support\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega un archivo del disco privado con un enlace firmado que caduca (ver App\Support\Media).
 */
class ArchivoPrivadoController extends Controller
{
    public function __invoke(string $clave): Response
    {
        $ruta = Media::decodificar($clave);
        abort_unless($ruta && Media::disco()->exists($ruta), 404);

        return Media::disco()->response($ruta, basename($ruta), [
            'Cache-Control' => 'private, max-age=600',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
