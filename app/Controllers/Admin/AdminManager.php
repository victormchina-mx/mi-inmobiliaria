<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use CodeIgniter\HTTP\RedirectResponse;

class AdminManager extends BaseController
{
    private AdminModel $adminModel;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
    }

    public function index(): string
    {
        return view('admin/admin_manager/index', [
            'pageTitle' => 'Administradores',
            'admins'    => $this->adminModel->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('admin/admin_manager/form', ['pageTitle' => 'Agregar Administrador']);
    }

    public function store(): RedirectResponse
    {
        $rules = [
            'auser'  => 'required|max_length[50]',
            'aemail' => 'required|valid_email',
            'apass'  => 'required|min_length[6]',
            'adob'   => 'permit_empty|valid_date',
            'aphone' => 'permit_empty|max_length[15]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->adminModel->insert([
            'auser'  => $this->request->getPost('auser'),
            'aemail' => $this->request->getPost('aemail'),
            'apass'  => $this->request->getPost('apass'),
            'adob'   => $this->request->getPost('adob'),
            'aphone' => $this->request->getPost('aphone'),
        ]);

        return redirect()->to('admin/admins')->with('success', 'Administrador creado correctamente.');
    }

    public function delete(int $aid): RedirectResponse
    {
        if ((int) session()->get('aid') === $aid) {
            return redirect()->to('admin/admins')->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $this->adminModel->delete($aid);

        return redirect()->to('admin/admins')->with('success', 'Administrador eliminado.');
    }
}
