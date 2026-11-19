<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HomeHero as Hero;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Portada del inicio: cambiar la foto de fondo de "Déjanos guiarte a
 * tu hogar" y su encuadre. Reglas y guardado: App\Libraries\HomeHero.
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
            'custom'    => Hero::isCustom(),
        ]);
    }

    public function save(): RedirectResponse
    {
        $position = (string) $this->request->getPost('position');
        $position = isset(Hero::POSITIONS[$position]) ? $position : 'center';
        $file     = $this->request->getFile('image');

        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $error = Hero::upload($file, $position);
            if ($error !== null) {
                return redirect()->to('admin/portada')->with('error', $error);
            }

            return redirect()->to('admin/portada')->with('success', 'Listo: la nueva foto ya está en la portada del inicio.');
        }

        Hero::setPosition($position);

        return redirect()->to('admin/portada')->with('success', 'Encuadre guardado.');
    }

    public function reset(): RedirectResponse
    {
        Hero::reset();

        return redirect()->to('admin/portada')->with('success', 'Se restauró la foto original de la portada.');
    }
}
