<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;

class Profile extends BaseController
{
    public function index(): string
    {
        $adminModel = new AdminModel();
        $admin = $adminModel->where('auser', session()->get('auser'))->first();

        return view('admin/profile', [
            'pageTitle' => 'Mi Perfil',
            'admin'     => $admin,
        ]);
    }
}
