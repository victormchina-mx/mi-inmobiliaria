<?php

namespace App\Models;

use CodeIgniter\Model;

class PropertyModel extends Model
{
    protected $table      = 'property';
    protected $primaryKey = 'pid';
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'title', 'pcontent', 'type', 'bhk', 'stype',
        'bedroom', 'bathroom', 'balcony', 'kitchen', 'hall',
        'floor', 'size', 'price', 'location', 'city', 'state',
        'feature', 'pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4',
        'uid', 'status', 'cancel_reason', 'mapimage', 'topmapimage', 'groundmapimage',
        'totalfloor', 'isFeatured',
        // Punto en el mapa (app/Database/sql/2026-11-property-coordinates.sql)
        'lat', 'lng', 'exact_location',
        // Texto propio de "¿Por qué vivir aquí?" (app/Database/sql/2026-11-property-why-text.sql)
        'why_text',
    ];

    protected $validationRules = [
        'title'  => 'required|max_length[200]',
        'type'   => 'required',
        'stype'  => 'required|in_list[sale,rent]',
        'price'  => 'required|decimal',
        'uid'    => 'required|is_natural_no_zero',
    ];

    /** Propiedades con datos del agente/dueño */
    public function withOwner(): static
    {
        return $this->select('property.*, user.uname, user.uemail, user.uphone, user.uimage, user.utype')
                    ->join('user', 'user.uid = property.uid');
    }

    /** Scope: solo propiedades destacadas (y visibles al público) */
    public function featured(): static
    {
        return $this->where('property.isFeatured', 1)->where('property.status', 'available');
    }

    /** Scope: solo propiedades visibles al público (estado Disponible) */
    public function available(): static
    {
        return $this->where('property.status', 'available');
    }

    /** Scope: propiedades de un usuario específico */
    public function byUser(int $uid): static
    {
        return $this->where('property.uid', $uid);
    }

    /** Scope: filtrar por estado geográfico */
    public function byState(string $state): static
    {
        return $this->where('property.state', $state);
    }

    /** Filtro combinado de búsqueda (grid) — solo propiedades visibles al público */
    public function applyFilters(array $filters): static
    {
        $this->where('property.status', 'available');

        if (! empty($filters['type'])) {
            $this->where('property.type', $filters['type']);
        }
        if (! empty($filters['stype'])) {
            $this->where('property.stype', $filters['stype']);
        }
        if (! empty($filters['city'])) {
            // El campo dice "Ciudad, colonia...": coincidencia parcial en
            // ciudad, ubicación (colonia/calle) o estado. like() escapa % y _.
            $this->groupStart()
                ->like('property.city', $filters['city'])
                ->orLike('property.location', $filters['city'])
                ->orLike('property.state', $filters['city'])
                ->groupEnd();
        }

        return $this;
    }

    /** Etiquetas en español para los mensajes de validación del modelo. */
    protected function initialize()
    {
        parent::initialize();
        $this->validationRules = \App\Libraries\FieldLabels::apply($this->validationRules);
    }
}
