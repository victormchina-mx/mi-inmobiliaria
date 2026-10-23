<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\FeedbackModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Profile extends BaseController
{
    public function index(): string
    {
        $userModel = new UserModel();
        $user = $userModel->find(session()->get('uid'));

        return view('user/profile', [
            'pageTitle' => 'Mi Perfil',
            'user'      => $user,
        ]);
    }

    public function submitFeedback(): RedirectResponse
    {
        $rules = [
            'content' => 'required|max_length[300]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', 'El comentario no puede estar vacío (máx. 300 caracteres).');
        }

        $feedbackModel = new FeedbackModel();
        $feedbackModel->insert([
            'uid'          => session()->get('uid'),
            'fdescription' => $this->request->getPost('content'),
            'status'       => 0,
        ]);

        return redirect()->to('profile')->with('success', 'Testimonio enviado. Está pendiente de aprobación.');
    }
}
