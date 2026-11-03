<?php

namespace App\Controllers\Api;

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
}
