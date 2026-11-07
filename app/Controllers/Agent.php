<?php

namespace App\Controllers;

use App\Models\UserModel;

class Agent extends BaseController
{
    public function index(): string
    {
        $userModel = new UserModel();

        return view('agent/index', [
            'pageTitle'       => 'Agentes inmobiliarios',
            'metaDescription' => 'Conoce a nuestros agentes inmobiliarios y contáctalos directamente por teléfono o correo para comprar, vender o rentar tu propiedad.',
            'agents'    => $userModel->agents()->findAll(),
        ]);
    }
}
