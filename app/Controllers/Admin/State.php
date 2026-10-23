<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StateModel;
use CodeIgniter\HTTP\RedirectResponse;

class State extends BaseController
{
    private StateModel $stateModel;

    public function __construct()
    {
        $this->stateModel = new StateModel();
    }

    public function index(): string
    {
        return view('admin/state/index', [
            'pageTitle' => 'Estados',
            'states'    => $this->stateModel->findAll(),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->validate(['sname' => 'required|max_length[100]'])) {
            return redirect()->back()->with('error', 'El nombre del estado es requerido.');
        }

        $this->stateModel->insert(['sname' => $this->request->getPost('sname')]);

        return redirect()->to('admin/states')->with('success', 'Estado agregado.');
    }

    public function edit(int $sid): string
    {
        return view('admin/state/edit', [
            'pageTitle' => 'Editar Estado',
            'state'     => $this->stateModel->find($sid),
        ]);
    }

    public function update(int $sid): RedirectResponse
    {
        if (! $this->validate(['sname' => 'required|max_length[100]'])) {
            return redirect()->back()->with('error', 'Nombre inválido.');
        }

        $this->stateModel->update($sid, ['sname' => $this->request->getPost('sname')]);

        return redirect()->to('admin/states')->with('success', 'Estado actualizado.');
    }

    public function delete(int $sid): RedirectResponse
    {
        $this->stateModel->delete($sid);

        return redirect()->to('admin/states')->with('success', 'Estado eliminado.');
    }
}
