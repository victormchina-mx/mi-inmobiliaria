<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AboutModel;
use CodeIgniter\HTTP\RedirectResponse;

class About extends BaseController
{
    private AboutModel $aboutModel;

    public function __construct()
    {
        $this->aboutModel = new AboutModel();
    }

    public function index(): string
    {
        return view('admin/about/index', [
            'pageTitle' => 'Gestión de Nosotros',
            'abouts'    => $this->aboutModel->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('admin/about/form', [
            'pageTitle' => 'Agregar Sección',
            'about'     => null,
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->validate(['title' => 'required|max_length[100]', 'content' => 'required'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $imageName = '';
        $file = $this->request->getFile('aimage');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $imageName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/about', $imageName);
        }

        $this->aboutModel->insert([
            'title'   => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'image'   => $imageName,
        ]);

        return redirect()->to('admin/about')->with('success', 'Sección creada correctamente.');
    }

    public function edit(int $id): string
    {
        return view('admin/about/form', [
            'pageTitle' => 'Editar Sección',
            'about'     => $this->aboutModel->find($id),
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        if (! $this->validate(['title' => 'required|max_length[100]', 'content' => 'required'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $existing  = $this->aboutModel->find($id);
        $imageName = $existing['image'] ?? '';

        $file = $this->request->getFile('aimage');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $imageName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/about', $imageName);
        }

        $this->aboutModel->update($id, [
            'title'   => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'image'   => $imageName,
        ]);

        return redirect()->to('admin/about')->with('success', 'Sección actualizada correctamente.');
    }

    public function delete(int $id): RedirectResponse
    {
        $about = $this->aboutModel->find($id);
        if ($about && ! empty($about['image'])) {
            $path = FCPATH . 'uploads/about/' . $about['image'];
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->aboutModel->delete($id);

        return redirect()->to('admin/about')->with('success', 'Sección eliminada.');
    }
}
