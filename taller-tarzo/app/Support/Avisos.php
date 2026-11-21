<?php

namespace App\Support;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Envía avisos del panel a los usuarios que tienen cierto permiso (p. ej. almacén).
 */
final class Avisos
{
    /**
     * @return Collection<int, User>
     */
    public static function usuariosCon(string $permiso): Collection
    {
        return User::query()->where('activo', true)->permission($permiso)->get();
    }

    public static function a(string $permiso, Notification $notificacion): void
    {
        $usuarios = self::usuariosCon($permiso);

        if ($usuarios->isNotEmpty()) {
            $notificacion->sendToDatabase($usuarios);
        }
    }
}
