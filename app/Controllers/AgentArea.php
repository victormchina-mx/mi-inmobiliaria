<?php

namespace App\Controllers;

use App\Libraries\Agency;
use App\Libraries\AgentListing;
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

        $uid        = (int) session()->get('uid');
        $properties = AgentListing::search($filters, $uid);   // solo las de su inmobiliaria

        // Opciones de los filtros, a partir de lo que ve el agente
        $all    = Agency::scopeProperties((new PropertyModel())->select('property.city, property.type, property.uid'), $uid)->findAll();
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
            'agency'     => Agency::ofUser($uid),
        ]);
    }

    public function ficha(int $pid): string|RedirectResponse
    {
        $property = (new PropertyModel())->withOwner()->find($pid);

        // Solo propiedades de su inmobiliaria (o las suyas si es independiente)
        if (! $property || ! Agency::canSee($property, (int) session()->get('uid'))) {
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
            // Logo y colores de la inmobiliaria del inmueble (o Real Estate by Vick)
            'brand'      => Agency::brand(Agency::find(isset($property['agency_id']) ? (int) $property['agency_id'] : null)),
            'template'   => $template,
            'useOwner'   => $useOwner,
            'isOwner'    => (int) $property['uid'] === (int) session()->get('uid'),
            'ownerName'  => $property['uname'],
        ]);
    }
}
