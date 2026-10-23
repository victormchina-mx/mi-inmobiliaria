<?php

namespace App\Controllers;

use App\Models\UserModel;

class Agent extends BaseController
{
    public function index(): string
    {
        $userModel = new UserModel();

        return view('agent/index', [
            'pageTitle' => 'Agentes',
            'agents'    => $userModel->agents()->findAll(),
        ]);
    }
}
