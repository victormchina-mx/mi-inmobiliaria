<?php

namespace App\Controllers;

use App\Models\AboutModel;

class About extends BaseController
{
    public function index(): string
    {
        $aboutModel = new AboutModel();

        return view('about/index', [
            'pageTitle'       => 'Nosotros',
            'metaDescription' => 'Conoce Real Estate by Vick: te ayudamos a comprar, vender y rentar casas, departamentos, oficinas y terrenos con asesoría personalizada.',
            'about'     => $aboutModel->first(),
        ]);
    }
}
