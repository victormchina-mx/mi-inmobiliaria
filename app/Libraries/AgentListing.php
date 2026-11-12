<?php

namespace App\Libraries;

use App\Models\PropertyModel;

/**
 * Búsqueda de "Todas las propiedades" del área de agentes: la usan la web
 * (AgentArea::properties) y la app móvil (Api\Agent::properties).
 *
 * Filtros: q (código VMCH-0025 / 25, o texto), stype, type, city, agent,
 * keys y status ('all' = todos los estados).
 */
class AgentListing
{
    public static function search(array $filters): array
    {
        $filters += ['q' => '', 'stype' => '', 'type' => '', 'city' => '', 'agent' => '', 'keys' => '', 'status' => 'available'];

        $model = (new PropertyModel())->withOwner();

        if ($filters['status'] !== 'all') {
            $model->where('property.status', $filters['status']);
        }
        if ($filters['stype'] !== '') {
            $model->where('property.stype', $filters['stype']);
        }
        if ($filters['type'] !== '') {
            $model->where('property.type', $filters['type']);
        }
        if ($filters['city'] !== '') {
            $model->where('property.city', $filters['city']);
        }
        if ($filters['agent'] !== '') {
            $model->where('property.uid', (int) $filters['agent']);
        }
        if ($filters['keys'] !== '' && PropertyExtras::ready()) {
            $model->where('property.keys_status', $filters['keys']);
        }
        if ($filters['q'] !== '') {
            // "VMCH-0025", "0025" o "25" buscan por código; lo demás por texto
            if (preg_match('/^(?:vmch-?)?0*(\d+)$/i', $filters['q'], $m)) {
                $model->where('property.pid', (int) $m[1]);
            } else {
                $model->groupStart()
                    ->like('property.title', $filters['q'])
                    ->orLike('property.location', $filters['q'])
                    ->orLike('property.city', $filters['q'])
                    ->orLike('property.state', $filters['q'])
                    ->groupEnd();
            }
        }

        return $model->orderBy('property.pid', 'DESC')->findAll();
    }
}
