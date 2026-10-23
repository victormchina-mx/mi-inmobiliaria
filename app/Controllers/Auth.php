<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserPasswordResetModel;
use CodeIgniter\HTTP\RedirectResponse;

class Auth extends BaseController
{
    public function login(): string
    {
        if (session()->get('uid')) {
            return redirect()->to('/');
        }

        return view('auth/login', ['pageTitle' => 'Iniciar Sesión']);
    }

    public function doLogin(): RedirectResponse
    {
        $rules = [
            'email' => 'required|valid_email',
            'pass'  => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userModel = new UserModel();
        $user = $userModel->where('uemail', $this->request->getPost('email'))->first();

        if (! $user || ! password_verify($this->request->getPost('pass'), $user['upass'])) {
            return redirect()->back()->withInput()->with('error', 'Email o contraseña incorrectos.');
        }

        session()->set([
            'uid'    => $user['uid'],
            'uemail' => $user['uemail'],
            'uname'  => $user['uname'],
            'utype'  => $user['utype'],
        ]);

        return redirect()->to('/')->with('success', 'Bienvenido, ' . esc($user['uname']) . '.');
    }

    public function register(): string
    {
        if (session()->get('uid')) {
            return redirect()->to('/');
        }

        return view('auth/register', ['pageTitle' => 'Registro']);
    }

    public function doRegister(): RedirectResponse
    {
        $rules = [
            'uname'  => 'required|max_length[100]',
            'email'  => 'required|valid_email|is_unique[user.uemail]',
            'phone'  => 'permit_empty|max_length[20]',
            'pass'   => 'required|min_length[6]',
            'utype'  => 'required|in_list[user,agent,builder]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $imageName = '';
        $file = $this->request->getFile('uimage');

        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (! in_array($file->getMimeType(), $allowed, true)) {
                return redirect()->back()->withInput()->with('error', 'Solo se permiten imágenes (jpg, png, gif, webp).');
            }
            $imageName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/user', $imageName);
        }

        $userModel = new UserModel();
        $userModel->insert([
            'uname'  => $this->request->getPost('uname'),
            'uemail' => $this->request->getPost('email'),
            'uphone' => $this->request->getPost('phone'),
            'upass'  => $this->request->getPost('pass'),
            'utype'  => $this->request->getPost('utype'),
            'uimage' => $imageName,
        ]);

        return redirect()->to('login')->with('success', 'Registro exitoso. Inicia sesión.');
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('login')->with('success', 'Sesión cerrada correctamente.');
    }

    // ── Recuperación de contraseña ────────────────────────────────────────────

    public function forgotPassword(): string|RedirectResponse
    {
        if (session()->get('uid')) {
            return redirect()->to('/');
        }

        return view('auth/forgot_password');
    }

    public function sendResetLink(): RedirectResponse
    {
        $email = trim($this->request->getPost('email'));

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Ingresa un correo electrónico válido.');
        }

        $genericMessage = 'Si ese correo está registrado, recibirás un enlace en los próximos minutos. Revisa también tu carpeta de spam.';

        $userModel = new UserModel();
        $user = $userModel->where('uemail', $email)->first();

        if (! $user) {
            return redirect()->to('forgot-password')->with('success', $genericMessage);
        }

        $resetModel = new UserPasswordResetModel();
        $plainToken = $resetModel->createToken($email);
        $resetUrl   = site_url('reset-password/' . $plainToken);

        $emailBody = view('auth/emails/reset_password', [
            'userName' => $user['uname'],
            'resetUrl' => $resetUrl,
        ]);

        $emailService = \Config\Services::email();
        $emailService->setTo($email);
        $emailService->setSubject('Restablecer contraseña — Real Estate');
        $emailService->setMessage($emailBody);
        $emailService->setMailType('html');

        if (! $emailService->send(false)) {
            log_message('error', 'Error enviando email de reset usuario a ' . $email . ': ' . $emailService->printDebugger(['headers']));
            return redirect()->back()->with('error', 'No se pudo enviar el correo. Intenta de nuevo más tarde.');
        }

        return redirect()->to('forgot-password')->with('success', $genericMessage);
    }

    public function resetPassword(string $token): string|RedirectResponse
    {
        if (session()->get('uid')) {
            return redirect()->to('/');
        }

        $resetModel = new UserPasswordResetModel();

        if (! $resetModel->findValidToken($token)) {
            return redirect()->to('forgot-password')
                             ->with('error', 'El enlace no es válido o ha expirado. Solicita uno nuevo.');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function doResetPassword(string $token): RedirectResponse
    {
        $resetModel = new UserPasswordResetModel();
        $record     = $resetModel->findValidToken($token);

        if (! $record) {
            return redirect()->to('forgot-password')
                             ->with('error', 'El enlace no es válido o ha expirado. Solicita uno nuevo.');
        }

        $newPass     = $this->request->getPost('pass');
        $confirmPass = $this->request->getPost('pass_confirm');

        if (! $newPass || strlen($newPass) < 6) {
            return redirect()->back()->with('error', 'La contraseña debe tener al menos 6 caracteres.');
        }

        if ($newPass !== $confirmPass) {
            return redirect()->back()->with('error', 'Las contraseñas no coinciden.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('uemail', $record['uemail'])->first();

        if (! $user) {
            return redirect()->to('login')->with('error', 'No se encontró la cuenta asociada.');
        }

        $db = \Config\Database::connect();
        $db->query("UPDATE user SET upass = ? WHERE uid = ?", [
            password_hash($newPass, PASSWORD_BCRYPT),
            (int) $user['uid'],
        ]);

        if ($db->affectedRows() === 0) {
            log_message('error', 'doResetPassword user: 0 filas afectadas para uid=' . $user['uid']);
            return redirect()->back()->with('error', 'No se pudo actualizar la contraseña. Intenta de nuevo.');
        }

        $resetModel->invalidateToken($token);

        return redirect()->to('login')
                         ->with('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
    }
}
