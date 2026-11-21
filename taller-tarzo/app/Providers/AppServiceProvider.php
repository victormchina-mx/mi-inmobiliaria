<?php

namespace App\Providers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En producción todo va por HTTPS (Hostinger y VPS).
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Detecta consultas N+1 y atributos inexistentes mientras se desarrolla.
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->uncompromised()
            : Password::min(8));

        // El dueño tiene todos los permisos.
        Gate::before(fn (User $user) => $user->hasRole(Rol::Dueno->value) ? true : null);

        Event::listen(Login::class, function (Login $evento): void {
            if ($evento->user instanceof User) {
                $evento->user->forceFill(['ultimo_acceso_at' => now()])->saveQuietly();
            }
        });
    }
}
