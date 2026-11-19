<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Backdrop;
use App\Libraries\HomeHero as Hero;
use App\Libraries\PageBanner;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Fondos: cambiar la foto de fondo, su encuadre y la intensidad del
 * degradado oscuro de:
 *   - la portada del inicio ("Déjanos guiarte a tu hogar")  → /admin/portada
 *   - el banner de las páginas interiores (Nosotros, Contacto, Propiedades,
 *     Agentes…)                                            → /admin/banner
 * Reglas y guardado: App\Libraries\Backdrop (HomeHero, PageBanner).
 */
class HomeHero extends BaseController
{
    private const SLOTS = [
        'portada' => [
            'class'   => Hero::class,
            'title'   => 'Portada del inicio',
            'kind'    => 'home',
            'tip'     => 'Mejor si el lado izquierdo es sencillo: ahí va el texto.',
            'saved'   => 'Listo: la nueva foto ya está en la portada del inicio.',
            'reset'   => 'Se restauró la portada original del inicio.',
            'viewUrl' => '/',
        ],
        'banner' => [
            'class'   => PageBanner::class,
            'title'   => 'Banner de páginas',
            'kind'    => 'pages',
            'tip'     => 'Es una franja ancha y baja: elige una foto con el motivo al centro.',
            'saved'   => 'Listo: la nueva foto ya está en el banner de Nosotros, Contacto, Propiedades, Agentes y demás páginas.',
            'reset'   => 'Se restauró el banner original de las páginas.',
            'viewUrl' => 'about',
        ],
    ];

    public function index(): string
    {
        return $this->show('portada');
    }

    public function save(): RedirectResponse
    {
        return $this->store('portada');
    }

    public function reset(): RedirectResponse
    {
        return $this->restore('portada');
    }

    public function banner(): string
    {
        return $this->show('banner');
    }

    public function bannerSave(): RedirectResponse
    {
        return $this->store('banner');
    }

    public function bannerReset(): RedirectResponse
    {
        return $this->restore('banner');
    }

    // ---------------------------------------------------------------

    private function show(string $slot): string
    {
        $cfg = self::SLOTS[$slot];
        /** @var class-string<Backdrop> $bg */
        $bg = $cfg['class'];

        return view('admin/home_hero/index', [
            'pageTitle' => $cfg['title'],
            'cfg'       => $cfg,
            'slot'      => $slot,
            'url'       => $bg::url(),
            'position'  => $bg::position(),
            'shade'     => $bg::shade(),
            'custom'    => $bg::isCustom(),
        ]);
    }

    private function store(string $slot): RedirectResponse
    {
        $cfg = self::SLOTS[$slot];
        /** @var class-string<Backdrop> $bg */
        $bg       = $cfg['class'];
        $position = (string) $this->request->getPost('position');
        $position = isset(Backdrop::POSITIONS[$position]) ? $position : 'center';
        $shade    = $bg::clampShade($this->request->getPost('shade') ?? Backdrop::SHADE_DEFAULT);
        $file     = $this->request->getFile('image');

        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $error = $bg::upload($file, $position, $shade);
            if ($error !== null) {
                return redirect()->to('admin/' . $slot)->with('error', $error);
            }

            return redirect()->to('admin/' . $slot)->with('success', $cfg['saved']);
        }

        $bg::setLook($position, $shade);

        return redirect()->to('admin/' . $slot)->with('success', 'Cambios guardados: encuadre y degradado.');
    }

    private function restore(string $slot): RedirectResponse
    {
        $bg = self::SLOTS[$slot]['class'];
        $bg::reset();

        return redirect()->to('admin/' . $slot)->with('success', self::SLOTS[$slot]['reset']);
    }
}
