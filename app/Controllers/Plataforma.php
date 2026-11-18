<?php

namespace App\Controllers;

use App\Filters\AgentOnlyFilter;
use App\Libraries\Agency;
use App\Models\PropertyModel;
use App\Models\UserModel;
use Throwable;

/**
 * Landing page de la plataforma (/plataforma): qué ofrece a quienes buscan
 * propiedad, a los agentes y a las inmobiliarias. Pensada para enlazarla
 * desde redes sociales.
 *
 * Vista: app/Views/landing/index.php · Estilos: public/css/landing.css
 */
class Plataforma extends BaseController
{
    public function index(): string
    {
        return view('landing/index', [
            'pageTitle'       => 'Publica y administra tus propiedades',
            'metaDescription' => 'Inmobiliaria by VMCH: encuentra casas, departamentos y terrenos de inmobiliarias y agentes de confianza. Para agentes: agenda de citas compartida, fichas técnicas con tu marca y app móvil.',
            'stats'           => $this->stats(),
            'showcase'        => $this->showcase(),
            'loggedIn'        => (bool) session()->get('uid'),
        ]);
    }

    /** Números reales para la franja de confianza (se ocultan los que estén en 0). */
    private function stats(): array
    {
        try {
            $stats = [
                'properties' => (new PropertyModel())->available()->countAllResults(),
                'agents'     => (new UserModel())->whereIn('utype', AgentOnlyFilter::TYPES)->countAllResults(),
                'agencies'   => Agency::ready() ? count(Agency::all(true)) : 0,
            ];
        } catch (Throwable) {
            $stats = [];
        }

        return array_filter($stats);
    }

    /** Las 3 propiedades disponibles más recientes con foto. */
    private function showcase(): array
    {
        try {
            return (new PropertyModel())->available()
                ->where('property.pimage !=', '')
                ->orderBy('property.pid', 'DESC')
                ->findAll(3);
        } catch (Throwable) {
            return [];
        }
    }
}
