<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ContactModel;
use CodeIgniter\HTTP\RedirectResponse;

class Contact extends BaseController
{
    public function index(): string
    {
        $contactModel = new ContactModel();

        return view('admin/contact/index', [
            'pageTitle' => 'Mensajes de Contacto',
            'contacts'  => $contactModel->orderBy('cid', 'DESC')->findAll(),
        ]);
    }

    public function delete(int $cid): RedirectResponse
    {
        $contactModel = new ContactModel();
        $contactModel->delete($cid);

        return redirect()->to('admin/contacts')->with('success', 'Mensaje eliminado.');
    }
}
