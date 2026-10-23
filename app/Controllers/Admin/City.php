<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CityModel;
use App\Models\StateModel;
use CodeIgniter\HTTP\RedirectResponse;

class City extends BaseController
{
    private CityModel $cityModel;

    public function __construct()
    {
        $this->cityModel = new CityModel();
    }

    public function index(): string
    {
        $stateModel = new StateModel();

        return view('admin/city/index', [
            'pageTitle' => 'Ciudades',
            'cities'    => $this->cityModel->withState()->findAll(),
            'states'    => $stateModel->findAll(),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->validate(['cname' => 'required|max_length[100]', 'sid' => 'required|is_natural_no_zero'])) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->cityModel->insert([
            'cname' => $this->request->getPost('cname'),
            'sid'   => (int) $this->request->getPost('sid'),
        ]);

        return redirect()->to('admin/cities')->with('success', 'Ciudad agregada.');
    }

    public function edit(int $cid): string
    {
        $stateModel = new StateModel();

        return view('admin/city/edit', [
            'pageTitle' => 'Editar Ciudad',
            'city'      => $this->cityModel->find($cid),
            'states'    => $stateModel->findAll(),
        ]);
    }

    public function update(int $cid): RedirectResponse
    {
        if (! $this->validate(['cname' => 'required|max_length[100]', 'sid' => 'required|is_natural_no_zero'])) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->cityModel->update($cid, [
            'cname' => $this->request->getPost('cname'),
            'sid'   => (int) $this->request->getPost('sid'),
        ]);

        return redirect()->to('admin/cities')->with('success', 'Ciudad actualizada.');
    }

    public function delete(int $cid): RedirectResponse
    {
        $this->cityModel->delete($cid);

        return redirect()->to('admin/cities')->with('success', 'Ciudad eliminada.');
    }
}
