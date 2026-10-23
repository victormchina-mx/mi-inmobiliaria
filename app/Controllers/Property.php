<?php

namespace App\Controllers;

use App\Models\PropertyModel;

class Property extends BaseController
{
    private PropertyModel $propertyModel;

    public function __construct()
    {
        $this->propertyModel = new PropertyModel();
    }

    public function index(): string
    {
        return view('property/index', [
            'pageTitle'  => 'Propiedades',
            'properties' => $this->propertyModel->withOwner()->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function show(int $pid): string
    {
        $property = $this->propertyModel->withOwner()->find($pid);

        if (! $property) {
            return redirect()->to('property')->with('error', 'Propiedad no encontrada.');
        }

        return view('property/detail', [
            'pageTitle' => esc($property['title']),
            'property'  => $property,
            'featured'  => $this->propertyModel->withOwner()->featured()->findAll(),
        ]);
    }

    public function grid(): string
    {
        $filters = [
            'type'  => $this->request->getPost('type') ?? $this->request->getGet('type'),
            'stype' => $this->request->getPost('stype') ?? $this->request->getGet('stype'),
            'city'  => $this->request->getPost('city') ?? $this->request->getGet('city'),
        ];

        $properties = $this->propertyModel->withOwner()->applyFilters($filters)->findAll();

        return view('property/grid', [
            'pageTitle'  => 'Buscar Propiedades',
            'properties' => $properties,
            'filters'    => $filters,
        ]);
    }

    public function byState(int $id): string
    {
        $property = $this->propertyModel->find($id);
        $state    = $property['state'] ?? '';

        return view('property/by_state', [
            'pageTitle'  => 'Propiedades en ' . esc($state),
            'state'      => $state,
            'properties' => $this->propertyModel->withOwner()->byState($state)->findAll(),
        ]);
    }
}
