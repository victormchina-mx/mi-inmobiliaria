<?php

namespace App\Support;

use App\Models\OrdenServicio;
use Illuminate\Support\Facades\URL;

/**
 * Enlace del portal de seguimiento de una orden (sin contraseña, firmado, válido 90 días).
 */
final class EnlacePortal
{
    public const DIAS_VIGENCIA = 90;

    public static function generar(OrdenServicio $orden): string
    {
        return url(URL::temporarySignedRoute('portal.orden', now()->addDays(self::DIAS_VIGENCIA), ['orden' => $orden->id], absolute: false));
    }
}
