<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\PropertyModel;
use CodeIgniter\HTTP\RedirectResponse;

class MyProperty extends BaseController
{
    private PropertyModel $propertyModel;

    public function __construct()
    {
        $this->propertyModel = new PropertyModel();
    }

    public function index(): string
    {
        return view('user/my_properties', [
            'pageTitle'  => 'Mis Propiedades',
            'properties' => $this->propertyModel->byUser(session()->get('uid'))->findAll(),
        ]);
    }

    public function create(): string
    {
        return view('user/property_form', [
            'pageTitle' => 'Publicar Propiedad',
            'property'  => null,
        ]);
    }

    public function store(): RedirectResponse
    {
        $rules = [
            'title'  => 'required|max_length[200]',
            'ptype'  => 'required',
            'stype'  => 'required|in_list[sale,rent]',
            'price'  => 'required|decimal',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $images = $this->uploadPropertyImages();

        $this->propertyModel->insert([
            'title'          => $this->request->getPost('title'),
            'pcontent'       => $this->request->getPost('content'),
            'type'           => $this->request->getPost('ptype'),
            'bhk'            => $this->request->getPost('bhk'),
            'stype'          => $this->request->getPost('stype'),
            'bedroom'        => (int) $this->request->getPost('bed'),
            'bathroom'       => (int) $this->request->getPost('bath'),
            'balcony'        => (int) $this->request->getPost('balc'),
            'kitchen'        => (int) $this->request->getPost('kitc'),
            'hall'           => (int) $this->request->getPost('hall'),
            'floor'          => $this->request->getPost('floor'),
            'size'           => (int) $this->request->getPost('asize'),
            'price'          => (float) $this->request->getPost('price'),
            'location'       => $this->request->getPost('loc'),
            'city'           => $this->request->getPost('city'),
            'state'          => $this->request->getPost('state'),
            'feature'        => $this->request->getPost('feature'),
            'totalfloor'     => $this->request->getPost('totalfl'),
            'status'         => $this->request->getPost('status'),
            'isFeatured'     => (int) $this->request->getPost('isFeatured'),
            'uid'            => session()->get('uid'),
            'pimage'         => $images['pimage'],
            'pimage1'        => $images['pimage1'],
            'pimage2'        => $images['pimage2'],
            'pimage3'        => $images['pimage3'],
            'pimage4'        => $images['pimage4'],
            'mapimage'       => $images['mapimage'],
            'topmapimage'    => $images['topmapimage'],
            'groundmapimage' => $images['groundmapimage'],
        ]);

        return redirect()->to('my-properties')->with('success', 'Propiedad publicada correctamente.');
    }

    public function edit(int $pid): string|RedirectResponse
    {
        $property = $this->propertyModel->find($pid);

        if (! $property || (int) $property['uid'] !== (int) session()->get('uid')) {
            return redirect()->to('my-properties')->with('error', 'Propiedad no encontrada.');
        }

        return view('user/property_form', [
            'pageTitle' => 'Editar Propiedad',
            'property'  => $property,
        ]);
    }

    public function update(int $pid): RedirectResponse
    {
        $property = $this->propertyModel->find($pid);

        if (! $property || (int) $property['uid'] !== (int) session()->get('uid')) {
            return redirect()->to('my-properties')->with('error', 'Acción no permitida.');
        }

        $rules = [
            'title'  => 'required|max_length[200]',
            'ptype'  => 'required',
            'stype'  => 'required|in_list[sale,rent]',
            'price'  => 'required|decimal',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $images = $this->uploadPropertyImages($property);

        $this->propertyModel->update($pid, [
            'title'          => $this->request->getPost('title'),
            'pcontent'       => $this->request->getPost('content'),
            'type'           => $this->request->getPost('ptype'),
            'bhk'            => $this->request->getPost('bhk'),
            'stype'          => $this->request->getPost('stype'),
            'bedroom'        => (int) $this->request->getPost('bed'),
            'bathroom'       => (int) $this->request->getPost('bath'),
            'balcony'        => (int) $this->request->getPost('balc'),
            'kitchen'        => (int) $this->request->getPost('kitc'),
            'hall'           => (int) $this->request->getPost('hall'),
            'floor'          => $this->request->getPost('floor'),
            'size'           => (int) $this->request->getPost('asize'),
            'price'          => (float) $this->request->getPost('price'),
            'location'       => $this->request->getPost('loc'),
            'city'           => $this->request->getPost('city'),
            'state'          => $this->request->getPost('state'),
            'feature'        => $this->request->getPost('feature'),
            'totalfloor'     => $this->request->getPost('totalfl'),
            'status'         => $this->request->getPost('status'),
            'isFeatured'     => (int) $this->request->getPost('isFeatured'),
            'pimage'         => $images['pimage'],
            'pimage1'        => $images['pimage1'],
            'pimage2'        => $images['pimage2'],
            'pimage3'        => $images['pimage3'],
            'pimage4'        => $images['pimage4'],
            'mapimage'       => $images['mapimage'],
            'topmapimage'    => $images['topmapimage'],
            'groundmapimage' => $images['groundmapimage'],
        ]);

        return redirect()->to('my-properties')->with('success', 'Propiedad actualizada correctamente.');
    }

    public function delete(int $pid): RedirectResponse
    {
        $property = $this->propertyModel->find($pid);

        if (! $property || (int) $property['uid'] !== (int) session()->get('uid')) {
            return redirect()->to('my-properties')->with('error', 'Acción no permitida.');
        }

        $this->propertyModel->delete($pid);

        return redirect()->to('my-properties')->with('success', 'Propiedad eliminada.');
    }

    /** Sube las 8 imágenes de la propiedad; mantiene las existentes si no se sube ninguna. */
    private function uploadPropertyImages(array $existing = []): array
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fields  = ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4', 'mapimage', 'topmapimage', 'groundmapimage'];
        $input   = ['pimage' => 'aimage', 'pimage1' => 'aimage1', 'pimage2' => 'aimage2',
                    'pimage3' => 'aimage3', 'pimage4' => 'aimage4',
                    'mapimage' => 'fimage', 'topmapimage' => 'fimage1', 'groundmapimage' => 'fimage2'];

        $result = [];
        foreach ($fields as $field) {
            $file = $this->request->getFile($input[$field]);
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
