<?php

namespace App\Controllers;

use App\Libraries\FichaTecnica;
use App\Libraries\PropertyExtras;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Área de agentes (filtro "agent-only"): todas las propiedades de todos los
 * agentes con sus botones de Agendar cita y Ficha técnica.
 *
 * Vistas: app/Views/agent/properties.php y app/Views/agent/ficha.php
 */
class AgentArea extends BaseController
{
    /** Estados que se pueden filtrar (y su texto). */
    public const STATUSES = [
        'available' => 'Disponibles',
        'sold'      => 'Vendidas',
        'rented'    => 'Rentadas',
        'cancelled' => 'Canceladas',
        'all'       => 'Todas',
    ];

    public function properties(): string
    {
        $get = function (string $name): string {
            $value = $this->request->getGet($name);

            return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
        };

        $filters = [
            'q'      => $get('q'),
            'stype'  => in_array($get('stype'), ['sale', 'rent'], true) ? $get('stype') : '',
            'type'   => $get('type'),
            'city'   => $get('city'),
            'agent'  => ctype_digit($get('agent')) ? $get('agent') : '',
            'keys'   => isset(PropertyExtras::KEYS[$get('keys')]) ? $get('keys') : '',
            'status' => isset(self::STATUSES[$get('status')]) ? $get('status') : 'available',
        ];

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

        $properties = $model->orderBy('property.pid', 'DESC')->findAll();

        // Opciones de los filtros, a partir de lo que existe
        $all    = (new PropertyModel())->select('property.city, property.type, property.uid')->findAll();
        $cities = array_values(array_unique(array_filter(array_map(static fn ($p) => trim((string) $p['city']), $all))));
        $types  = array_values(array_unique(array_filter(array_column($all, 'type'))));
        sort($cities);
        $agentIds = array_values(array_unique(array_map('intval', array_column($all, 'uid'))));
        $agents   = $agentIds ? (new UserModel())->select('uid, uname')->whereIn('uid', $agentIds)->orderBy('uname')->findAll() : [];

        return view('agent/properties', [
            'pageTitle'  => 'Todas las propiedades',
            'noindex'    => true,
            'properties' => $properties,
            'filters'    => $filters,
            'cities'     => $cities,
            'types'      => $types,
            'agents'     => $agents,
            'keysReady'  => PropertyExtras::ready(),
        ]);
    }

    public function ficha(int $pid): string|RedirectResponse
    {
        $property = (new PropertyModel())->withOwner()->find($pid);

        if (! $property) {
            return redirect()->to('agente/propiedades')->with('error', 'Propiedad no encontrada.');
        }

        // Asesor de la ficha: quien la genera (por defecto) o el responsable del inmueble
        $useOwner = $this->request->getGet('asesor') === 'responsable';
        $me       = (new UserModel())->find((int) session()->get('uid')) ?? [];
        $agent    = $useOwner
            ? ['uname' => $property['uname'], 'uphone' => $property['uphone'], 'uimage' => $property['uimage'], 'utype' => $property['utype'] ?? 'agent']
            : $me;

        $template = (string) $this->request->getGet('tpl');
        if (! isset(FichaTecnica::TEMPLATES[$template])) {
            $template = 'elegante';
        }

        return view('agent/ficha', [
            'pageTitle'  => 'Ficha técnica ' . FichaTecnica::code($pid),
            'noindex'    => true,
            'property'   => $property,
            'ficha'      => FichaTecnica::data($property, $agent),
            'template'   => $template,
            'useOwner'   => $useOwner,
            'isOwner'    => (int) $property['uid'] === (int) session()->get('uid'),
            'ownerName'  => $property['uname'],
        ]);
    }
}
