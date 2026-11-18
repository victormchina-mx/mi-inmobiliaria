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
    /**
     * Oferta de lanzamiento "Socio fundador": quien se registre como agente,
     * inmobiliaria o constructora hasta OFFER_ENDS usa la plataforma gratis
     * hasta FREE_UNTIL. Pasada la fecha, la landing deja de mostrarla sola.
     */
    public const OFFER_ENDS = '2026-12-31';
    public const FREE_UNTIL = '2027-06-30';

    public function index(): string
    {
        $offer = $this->offer();

        return view('landing/index', [
            'pageTitle'       => 'Publica y administra tus propiedades',
            'metaDescription' => $offer
                ? 'Oferta de lanzamiento: agentes, inmobiliarias y constructoras que se registren antes del 31 de diciembre usan Inmobiliaria by VMCH gratis hasta el 30 de junio de 2027. Particulares publican gratis siempre.'
                : 'Inmobiliaria by VMCH: encuentra casas, departamentos y terrenos de inmobiliarias, constructoras y agentes de confianza. Para agentes: agenda de citas compartida, fichas técnicas con tu marca y app móvil.',
            'offer'           => $offer,
            'stats'           => $this->stats(),
            'showcase'        => $this->showcase(),
            'loggedIn'        => (bool) session()->get('uid'),
        ]);
    }

    /** Datos de la oferta mientras siga vigente (null cuando ya terminó). */
    private function offer(): ?array
    {
        $today = new \DateTimeImmutable('today');
        $ends  = new \DateTimeImmutable(self::OFFER_ENDS);
        if ($today > $ends) {
            return null;
        }

        return [
            'ends'      => '31 de diciembre de 2026',
            'freeUntil' => '30 de junio de 2027',
            'daysLeft'  => (int) $today->diff($ends)->days + 1,   // cuenta el día de hoy
        ];
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
