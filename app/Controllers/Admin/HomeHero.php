<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HomeHero as Hero;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Portada del inicio: cambiar la foto de fondo de "Déjanos guiarte a
 * tu hogar", su encuadre y la intensidad del degradado oscuro. Reglas y guardado: App\Libraries\HomeHero.
 */
class HomeHero extends BaseController
{
    public function index(): string
    {
        return view('admin/home_hero/index', [
            'pageTitle' => 'Portada del inicio',
            'url'       => Hero::url(),
            'default'   => base_url(Hero::DEFAULT),
            'position'  => Hero::position(),
            'shade'     => Hero::shade(),
            'custom'    => Hero::isCustom(),
        ]);
    }

    public function save(): RedirectResponse
    {
        $position = (string) $this->request->getPost('position');
        $position = isset(Hero::POSITIONS[$position]) ? $position : 'center';
        $shade    = Hero::clampShade($this->request->getPost('shade') ?? Hero::SHADE_DEFAULT);
        $file     = $this->request->getFile('image');

        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $error = Hero::upload($file, $position, $shade);
            if ($error !== null) {
                return redirect()->to('admin/portada')->with('error', $error);
            }

            return redirect()->to('admin/portada')->with('success', 'Listo: la nueva foto ya está en la portada del inicio.');
        }

        Hero::setLook($position, $shade);

        return redirect()->to('admin/portada')->with('success', 'Cambios guardados: encuadre y degradado.');
    }

    public function reset(): RedirectResponse
    {
        Hero::reset();

        return redirect()->to('admin/portada')->with('success', 'Se restauró la foto original de la portada.');
    }
}
