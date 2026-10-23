<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use CodeIgniter\HTTP\RedirectResponse;

class Register extends BaseController
{
    public function create(): RedirectResponse
    {
        $rules = [
            'auser'  => 'required|max_length[50]',
            'aemail' => 'required|valid_email',
            'apass'  => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $adminModel = new AdminModel();

        $data = [
            'auser'  => $this->request->getPost('auser'),
            'aemail' => $this->request->getPost('aemail'),
            'apass'  => $this->request->getPost('apass'),
            'aphone' => $this->request->getPost('aphone'),
            'adob'   => $this->request->getPost('adob') ?: null,
        ];

        if (! $adminModel->insert($data)) {
            return redirect()->back()->with('error', 'Error al crear el administrador.');
        }

        return redirect()->to('/')->with('success', 'Administrador creado exitosamente.');
    }
}
