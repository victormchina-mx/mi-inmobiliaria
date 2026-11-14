<?php

namespace App\Libraries;

use App\Models\PropertyModel;

/**
 * Búsqueda de "Todas las propiedades" del área de agentes: la usan la web
 * (AgentArea::properties) y la app móvil (Api\Agent::properties).
 *
 * Filtros: q (código A-0025 / 25, o texto), stype, type, city, agent,
 * keys y status ('all' = todos los estados).
 */
class AgentListing
{
    /**
     * @param int|null $uid usuario que busca: solo ve las propiedades de su
     *                      inmobiliaria (o las suyas si es independiente)
     */
    public static function search(array $filters, ?int $uid = null): array
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
            // "A-0025", "0025" o "25" buscan por código (también el anterior "VMCH-0025"); lo demás por texto
            if (preg_match('/^(?:a-?|vmch-?)?0*(\d+)$/i', $filters['q'], $m)) {
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

        if ($uid !== null) {
            Agency::scopeProperties($model, $uid);
        }

        return $model->orderBy('property.pid', 'DESC')->findAll();
    }
}
