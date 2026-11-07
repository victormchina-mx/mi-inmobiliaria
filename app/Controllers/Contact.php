<?php

namespace App\Controllers;

use App\Models\ContactModel;
use CodeIgniter\HTTP\RedirectResponse;

class Contact extends BaseController
{
    public function index(): string
    {
        return view('contact/index', [
            'pageTitle'       => 'Contacto',
            'metaDescription' => 'Escríbenos o llámanos al 55 7606 2384: te ayudamos a comprar, vender o rentar tu casa, departamento u oficina.',
        ]);
    }

    public function submit(): RedirectResponse
    {
        $contactModel = new ContactModel();

        if (! $this->validate($contactModel->validationRules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = [
            'name'    => $this->request->getPost('name'),
            'email'   => $this->request->getPost('email'),
            'phone'   => $this->request->getPost('phone'),
            'subject' => $this->request->getPost('subject'),
            'message' => $this->request->getPost('message'),
        ];

        $contactModel->insert($data);

        $emailService = \Config\Services::email();
        $emailService->setTo('inmobiliaria.info@vmch.net');
        $emailService->setReplyTo($data['email'], $data['name']);
        $emailService->setSubject('Nuevo mensaje de contacto: ' . $data['subject']);
        $emailService->setMessage(view('contact/emails/notify', $data));
        $emailService->setMailType('html');

        if (! $emailService->send(false)) {
            log_message('error', 'Error enviando email de contacto: ' . $emailService->printDebugger(['headers']));
        }

        return redirect()->to('contact')->with('success', 'Mensaje enviado correctamente. Nos pondremos en contacto pronto.');
    }
}
