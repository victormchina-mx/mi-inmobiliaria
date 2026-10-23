<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class User extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): string
    {
        return view('admin/user/index', [
            'pageTitle' => 'Usuarios',
            'users'     => $this->userModel->users()->findAll(),
            'type'      => 'user',
            'title'     => 'Usuarios',
        ]);
    }

    public function delete(int $uid): RedirectResponse
    {
        return $this->deleteByType($uid, 'admin/users');
    }

    public function agents(): string
    {
        return view('admin/user/index', [
            'pageTitle' => 'Agentes',
            'users'     => $this->userModel->agents()->findAll(),
            'type'      => 'agent',
            'title'     => 'Agentes',
        ]);
    }

    public function deleteAgent(int $uid): RedirectResponse
    {
        return $this->deleteByType($uid, 'admin/agents');
    }

    public function builders(): string
    {
        return view('admin/user/index', [
            'pageTitle' => 'Constructores',
            'users'     => $this->userModel->builders()->findAll(),
            'type'      => 'builder',
            'title'     => 'Constructores',
        ]);
    }

    public function deleteBuilder(int $uid): RedirectResponse
    {
        return $this->deleteByType($uid, 'admin/builders');
    }

    public function edit(int $uid): string|RedirectResponse
    {
        $user = $this->userModel->find($uid);

        if (! $user) {
            return redirect()->to('admin/users')->with('error', 'Usuario no encontrado.');
        }

        [$backUrl, $backLabel] = match($user['utype']) {
            'agent'   => ['admin/agents',   'Agentes'],
            'builder' => ['admin/builders', 'Constructores'],
            default   => ['admin/users',    'Usuarios'],
        };

        return view('admin/user/edit', [
            'pageTitle'  => 'Editar ' . $backLabel,
            'user'       => $user,
            'backUrl'    => $backUrl,
            'backLabel'  => $backLabel,
        ]);
    }

    public function update(int $uid): RedirectResponse
    {
        $user = $this->userModel->find($uid);

        if (! $user) {
            return redirect()->to('admin/users')->with('error', 'Usuario no encontrado.');
        }

        $rules = [
            'uname'  => 'required|max_length[100]',
            'uemail' => "required|valid_email|max_length[100]|is_unique[user.uemail,uid,{$uid}]",
            'uphone' => 'permit_empty|max_length[20]',
            'utype'  => 'required|in_list[user,agent,builder]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $newType = $this->request->getPost('utype');

        $data = [
            'uname'  => $this->request->getPost('uname'),
            'uemail' => $this->request->getPost('uemail'),
            'uphone' => $this->request->getPost('uphone'),
            'utype'  => $newType,
        ];

        $newPass = $this->request->getPost('upass');
        if ($newPass !== '' && $newPass !== null) {
            if (strlen($newPass) < 6) {
                return redirect()->back()->withInput()->with('error', 'La contraseña debe tener al menos 6 caracteres.');
            }
            $data['upass'] = $newPass;
        }

        $file = $this->request->getFile('uimage');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (! in_array($file->getMimeType(), $allowed, true)) {
                return redirect()->back()->withInput()->with('error', 'Solo se permiten imágenes (jpg, png, gif, webp).');
            }
            if (! empty($user['uimage'])) {
                $old = FCPATH . 'uploads/user/' . $user['uimage'];
                if (is_file($old)) {
                    unlink($old);
                }
            }
            $imageName      = $file->getRandomName();
            $file->move(FCPATH . 'uploads/user', $imageName);
            $data['uimage'] = $imageName;
        }

        $this->userModel->skipValidation(true)->update($uid, $data);

        $redirect = match($newType) {
            'agent'   => 'admin/agents',
            'builder' => 'admin/builders',
            default   => 'admin/users',
        };

        return redirect()->to($redirect)->with('success', 'Usuario actualizado correctamente.');
    }

    private function deleteByType(int $uid, string $redirect): RedirectResponse
    {
        $user = $this->userModel->find($uid);

        if ($user && ! empty($user['uimage'])) {
            $path = FCPATH . 'uploads/user/' . $user['uimage'];
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->userModel->delete($uid);

        return redirect()->to($redirect)->with('success', 'Usuario eliminado correctamente.');
    }
}
