<?php

namespace App\Controllers;

use App\Libraries\Geocoder;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Buscador de direcciones del mapa en el formulario de propiedades
 * (partials/location_picker.php → public/js/location-picker.js).
 *
 * GET /geocode?q=Popotla, Miguel Hidalgo → {ok, lat, lng, bbox}
 *
 * Solo con sesión (agente o admin) y con límite de uso por IP: cada
 * búsqueda nueva es una consulta a Nominatim, que pide no abusar.
 */
class Geocode extends BaseController
{
    public function search(): ResponseInterface
    {
        $session = session();

        if (! $session->get('uid') && ! $session->get('auser')) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'error' => 'Inicia sesión.']);
        }

        $query = $this->request->getGet('q');
        $query = is_string($query) ? mb_substr(trim($query), 0, 200) : '';

        if (mb_strlen($query) < 3) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Escribe una dirección más completa.']);
        }

        $place = (new Geocoder())->find($query);

        if ($place === null) {
            return $this->response->setJSON(['ok' => false, 'error' => 'No encontramos esa dirección. Prueba con la colonia y el municipio, o marca el punto en el mapa.']);
        }

        return $this->response->setJSON(['ok' => true] + $place);
    }
}
