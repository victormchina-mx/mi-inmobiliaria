<?php

namespace App\Controllers;

use App\Models\ContactModel;
use CodeIgniter\HTTP\RedirectResponse;

class Contact extends BaseController
{
    public function index(): string
    {
        return view('contact/index', ['pageTitle' => 'Contacto']);
    }

    public function submit(): RedirectResponse
    {
        $contactModel = new ContactModel();

        if (! $this->validate($contactModel->validationRules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $contactModel->insert([
            'name'    => $this->request->getPost('name'),
            'email'   => $this->request->getPost('email'),
            'phone'   => $this->request->getPost('phone'),
            'subject' => $this->request->getPost('subject'),
            'message' => $this->request->getPost('message'),
        ]);

        return redirect()->to('contact')->with('success', 'Mensaje enviado correctamente. Nos pondremos en contacto pronto.');
    }
}
