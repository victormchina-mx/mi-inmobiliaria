<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CrearUsuario extends Command
{
    protected $signature = 'tarzo:crear-usuario {email?} {--nombre=} {--rol=}';

    protected $description = 'Crea un usuario del panel y le asigna un rol (la contraseña se pide sin mostrarse).';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text('Correo electrónico', required: true);
        $nombre = $this->option('nombre') ?? text('Nombre completo', required: true);
        $rol = $this->option('rol') ?? select('Rol', collect(Rol::cases())->mapWithKeys(fn (Rol $r) => [$r->value => $r->getLabel()])->all(), Rol::Dueno->value);
        $clave = password('Contraseña (mínimo 10 caracteres, letras y números)', required: true);

        $validador = Validator::make(
            ['email' => $email, 'rol' => $rol, 'password' => $clave],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'rol' => ['required', 'in:'.implode(',', array_column(Rol::cases(), 'value'))],
                'password' => ['required', Password::min(10)->letters()->numbers()],
            ],
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $usuario = User::create(['name' => $nombre, 'email' => $email, 'password' => $clave, 'activo' => true]);
        $usuario->assignRole($rol);

        $this->info("Usuario {$email} creado con rol ".Rol::from($rol)->getLabel().'.');

        return self::SUCCESS;
    }
}
