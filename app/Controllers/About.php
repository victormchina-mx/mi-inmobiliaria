<?php

namespace App\Controllers;

use App\Models\AboutModel;

class About extends BaseController
{
    public function index(): string
    {
        $aboutModel = new AboutModel();

        return view('about/index', [
            'pageTitle' => 'Nosotros',
            'about'     => $aboutModel->first(),
        ]);
    }
}
