<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CityModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Property extends BaseController
{
    private PropertyModel $propertyModel;

    public function __construct()
    {
        $this->propertyModel = new PropertyModel();
    }

    public function index(): string
    {
        return view('admin/property/index', [
            'pageTitle'  => 'Gestión de Propiedades',
            'properties' => $this->propertyModel->withOwner()->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('admin/property/form', [
            'pageTitle' => 'Agregar Propiedad',
            'property'  => null,
            'users'     => (new UserModel())->findAll(),
            'cities'    => (new CityModel())->withState()->orderBy('state.sname', 'ASC')->orderBy('city.cname', 'ASC')->findAll(),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->validate(['title' => 'required', 'price' => 'required|decimal'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $images = $this->uploadImages();

        $this->propertyModel->insert(array_merge($this->getFormData(), $images));

        return redirect()->to('admin/property')->with('success', 'Propiedad creada correctamente.');
    }

    public function edit(int $pid): string
    {
        return view('admin/property/form', [
            'pageTitle' => 'Editar Propiedad',
            'property'  => $this->propertyModel->find($pid),
            'users'     => (new UserModel())->findAll(),
            'cities'    => (new CityModel())->withState()->orderBy('state.sname', 'ASC')->orderBy('city.cname', 'ASC')->findAll(),
        ]);
    }

    public function update(int $pid): RedirectResponse
    {
        if (! $this->validate(['title' => 'required', 'price' => 'required|decimal'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $existing = $this->propertyModel->find($pid);
        $images   = $this->uploadImages($existing);

        $this->propertyModel->update($pid, array_merge($this->getFormData(), $images));

        return redirect()->to('admin/property')->with('success', 'Propiedad actualizada correctamente.');
    }

    public function delete(int $pid): RedirectResponse
    {
        $this->propertyModel->delete($pid);

        return redirect()->to('admin/property')->with('success', 'Propiedad eliminada.');
    }

    private function getFormData(): array
    {
        return [
            'title'      => $this->request->getPost('title'),
            'pcontent'   => $this->request->getPost('content'),
            'type'       => $this->request->getPost('ptype'),
            'bhk'        => $this->request->getPost('bhk'),
            'stype'      => $this->request->getPost('stype'),
            'bedroom'    => (int) $this->request->getPost('bed'),
            'bathroom'   => (int) $this->request->getPost('bath'),
            'balcony'    => (int) $this->request->getPost('balc'),
            'kitchen'    => (int) $this->request->getPost('kitc'),
            'hall'       => (int) $this->request->getPost('hall'),
            'floor'      => $this->request->getPost('floor'),
            'size'       => (int) $this->request->getPost('asize'),
            'price'      => (float) $this->request->getPost('price'),
            'location'   => $this->request->getPost('loc'),
            'city'       => $this->request->getPost('city'),
            'state'      => $this->request->getPost('state'),
            'feature'    => $this->request->getPost('feature'),
            'totalfloor' => $this->request->getPost('totalfl'),
            'status'     => $this->request->getPost('status'),
            'isFeatured' => (int) $this->request->getPost('isFeatured'),
            'uid'        => (int) $this->request->getPost('uid'),
        ];
    }

    private function uploadImages(array $existing = []): array
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $map = [
            'pimage' => 'aimage', 'pimage1' => 'aimage1', 'pimage2' => 'aimage2',
            'pimage3' => 'aimage3', 'pimage4' => 'aimage4',
            'mapimage' => 'fimage', 'topmapimage' => 'fimage1', 'groundmapimage' => 'fimage2',
        ];

        $result = [];
        foreach ($map as $field => $input) {
            $file = $this->request->getFile($input);
            if ($file && $file->isValid() && ! $file->hasMoved() && in_array($file->getMimeType(), $allowed, true)) {
                $name = $file->getRandomName();
                $file->move(FCPATH . 'uploads/property', $name);
                $result[$field] = $name;
            } else {
                $result[$field] = $existing[$field] ?? '';
            }
        }

        return $result;
    }
}
