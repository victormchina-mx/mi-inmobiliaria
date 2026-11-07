<?php

namespace App\Controllers;

use App\Models\PropertyModel;
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
        return view('property/index', [
            'pageTitle'  => 'Propiedades',
            'properties' => $this->propertyModel->withOwner()->available()->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function show(int $pid): string|RedirectResponse
    {
        $property = $this->propertyModel->withOwner()->available()->find($pid);

        if (! $property) {
            return redirect()->to('property')->with('error', 'Propiedad no encontrada.');
        }

        return view('property/detail', [
            'pageTitle' => $property['title'], // el layout lo escapa
            'property'  => $property,
            'featured'  => $this->propertyModel->withOwner()->featured()->findAll(),
        ]);
    }

    public function grid(): string
    {
        // Solo textos: un parámetro enviado como lista (city[]=x) se ignora
        $param = function (string $name): string {
            $value = $this->request->getPost($name) ?? $this->request->getGet($name);

            return is_string($value) ? trim($value) : '';
        };

        $filters = [
            'type'  => $param('type'),
            'stype' => $param('stype'),
            'city'  => mb_substr($param('city'), 0, 100),
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
            'pageTitle'  => 'Propiedades en ' . $state, // el layout lo escapa
            'state'      => $state,
            'properties' => $this->propertyModel->withOwner()->available()->byState($state)->findAll(),
        ]);
    }
}
