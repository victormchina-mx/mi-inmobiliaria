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
        'uid', 'status', 'mapimage', 'topmapimage', 'groundmapimage',
        'totalfloor', 'isFeatured',
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

    /** Scope: solo propiedades destacadas */
    public function featured(): static
    {
        return $this->where('property.isFeatured', 1);
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

    /** Filtro combinado de búsqueda (grid) */
    public function applyFilters(array $filters): static
    {
        if (! empty($filters['type'])) {
            $this->where('property.type', $filters['type']);
        }
        if (! empty($filters['stype'])) {
            $this->where('property.stype', $filters['stype']);
        }
        if (! empty($filters['city'])) {
            $this->where('property.city', $filters['city']);
        }

        return $this;
    }
}
