<?php

namespace App\Controllers\Api;

use App\Libraries\Geocoder;
use App\Models\CityModel;

class Lookups extends BaseApiController
{
    /** Ciudades agrupadas por estado, mismo orden que usa el Select2 del formulario web. */
    public function cities()
    {
        $cities = (new CityModel())
            ->withState()
            ->orderBy('state.sname', 'ASC')
            ->orderBy('city.cname', 'ASC')
            ->findAll();

        $byState = [];
        foreach ($cities as $c) {
            $byState[$c['sname']][] = $c['cname'];
        }

        $states = [];
        foreach ($byState as $state => $cityNames) {
            $states[] = ['state' => $state, 'cities' => $cityNames];
        }

        return $this->jsonResponse(['states' => $states]);
    }

    /**
     * Buscador de direcciones del mapa de la app (pantalla "Ubicación en el
     * mapa"): GET /api/v1/geocode?q=… → {ok, lat, lng, bbox}.
     * Requiere sesión (api-auth) y como máximo 15 búsquedas por minuto por
     * usuario: cada búsqueda nueva es una consulta a Nominatim.
     */
    public function geocode()
    {
        $uid = (int) $this->currentUid();
        if (service('throttler')->check('api-geocode-' . $uid . '-' . md5($this->request->getIPAddress()), 15, MINUTE) === false) {
            return $this->jsonResponse(['ok' => false, 'error' => 'Demasiadas búsquedas seguidas. Espera un minuto.'], 429);
        }

        $query = $this->request->getGet('q');
        $query = is_string($query) ? mb_substr(trim($query), 0, 200) : '';

        if (mb_strlen($query) < 3) {
            return $this->jsonResponse(['ok' => false, 'error' => 'Escribe una dirección más completa.'], 422);
        }

        $place = (new Geocoder())->find($query);

        if ($place === null) {
            return $this->jsonResponse(['ok' => false, 'error' => 'No encontramos esa dirección. Prueba con la colonia y el municipio, o mueve el mapa.']);
        }

        return $this->jsonResponse(['ok' => true] + $place);
    }
}
