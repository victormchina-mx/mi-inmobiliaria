<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\AdminPasswordResetModel;
use CodeIgniter\HTTP\RedirectResponse;

class Auth extends BaseController
{
    public function login(): string|RedirectResponse
    {
        if (session()->get('auser')) {
            return redirect()->to('admin/dashboard');
        }

        return view('admin/login', ['pageTitle' => 'Admin Login']);
    }

    public function doLogin(): RedirectResponse
    {
        $rules = [
            'auser' => 'required',
            'apass' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', 'Por favor completa todos los campos.');
        }

        $adminModel = new AdminModel();
        $credential = $this->request->getPost('auser');

        $admin = $adminModel->groupStart()
                            ->where('auser', $credential)
                            ->orWhere('aemail', $credential)
                            ->groupEnd()
                            ->first();

        if (! $admin || ! password_verify($this->request->getPost('apass'), $admin['apass'])) {
            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }

        session()->set([
            'auser' => $admin['auser'],
            'aid'   => $admin['aid'],
        ]);

        return redirect()->to('admin/dashboard')->with('success', 'Bienvenido, ' . esc($admin['auser']) . '.');
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('admin/login');
    }

    // ── Recuperación de contraseña ────────────────────────────────────────────

    public function forgotPassword(): string|RedirectResponse
    {
        if (session()->get('auser')) {
            return redirect()->to('admin/dashboard');
        }

        return view('admin/forgot_password');
    }

    public function sendResetLink(): RedirectResponse
    {
        $email = trim($this->request->getPost('aemail'));

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Ingresa un correo electrónico válido.');
        }

        // Mensaje genérico siempre para no revelar si el email existe
        $genericMessage = 'Si ese correo está registrado, recibirás un enlace en los próximos minutos. Revisa también tu carpeta de spam.';

        $adminModel = new AdminModel();
        $admin = $adminModel->where('aemail', $email)->first();

        if (! $admin) {
            return redirect()->to('admin/forgot-password')->with('success', $genericMessage);
        }

        $resetModel  = new AdminPasswordResetModel();
        $plainToken  = $resetModel->createToken($email);
        $resetUrl    = site_url('admin/reset-password/' . $plainToken);

        $emailBody = view('admin/emails/reset_password', [
            'adminUser' => $admin['auser'],
            'resetUrl'  => $resetUrl,
        ]);

        $emailService = \Config\Services::email();
        $emailService->setTo($email);
        $emailService->setSubject('Restablecer contraseña — Panel Administrativo');
        $emailService->setMessage($emailBody);
        $emailService->setMailType('html');

        if (! $emailService->send(false)) {
            log_message('error', 'Error enviando email de reset admin a ' . $email . ': ' . $emailService->printDebugger(['headers']));
            return redirect()->back()->with('error', 'No se pudo enviar el correo. Intenta de nuevo más tarde.');
        }

        return redirect()->to('admin/forgot-password')->with('success', $genericMessage);
    }

    public function resetPassword(string $token): string|RedirectResponse
    {
        if (session()->get('auser')) {
            return redirect()->to('admin/dashboard');
        }

        $resetModel = new AdminPasswordResetModel();

        if (! $resetModel->findValidToken($token)) {
            return redirect()->to('admin/forgot-password')
                             ->with('error', 'El enlace no es válido o ha expirado. Solicita uno nuevo.');
        }

        return view('admin/reset_password', ['token' => $token]);
    }

    public function doResetPassword(string $token): RedirectResponse
    {
        $resetModel = new AdminPasswordResetModel();
        $record     = $resetModel->findValidToken($token);

        if (! $record) {
            return redirect()->to('admin/forgot-password')
                             ->with('error', 'El enlace no es válido o ha expirado. Solicita uno nuevo.');
        }

        $newPass    = $this->request->getPost('apass');
        $confirmPass = $this->request->getPost('apass_confirm');

        if (! $newPass || strlen($newPass) < 6) {
            return redirect()->back()->with('error', 'La contraseña debe tener al menos 6 caracteres.');
        }

        if ($newPass !== $confirmPass) {
            return redirect()->back()->with('error', 'Las contraseñas no coinciden.');
        }

        $adminModel = new AdminModel();
        $admin = $adminModel->where('aemail', $record['aemail'])->first();

        if (! $admin) {
            return redirect()->to('admin/login')->with('error', 'No se encontró la cuenta asociada.');
        }

        $db   = \Config\Database::connect();
        $hash = password_hash($newPass, PASSWORD_BCRYPT);

        $db->query("UPDATE admin SET apass = ? WHERE aid = ?", [$hash, (int) $admin['aid']]);

        if ($db->affectedRows() === 0) {
            log_message('error', 'doResetPassword: 0 filas afectadas para aid=' . $admin['aid']);
            return redirect()->back()->with('error', 'No se pudo actualizar la contraseña. Intenta de nuevo.');
        }

        $resetModel->invalidateToken($token);

        return redirect()->to('admin/login')
                         ->with('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
    }
}
