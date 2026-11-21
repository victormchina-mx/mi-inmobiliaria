<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Acceso único al disco privado de fotos, video, firmas y documentos (config tarzo.media.disco).
 */
final class Media
{
    public static function disco(): Filesystem
    {
        return Storage::disk(self::nombreDisco());
    }

    public static function nombreDisco(): string
    {
        return config('tarzo.media.disco');
    }

    public static function urlTemporal(?string $ruta, int $minutos = 30): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        return self::disco()->temporaryUrl($ruta, now()->addMinutes($minutos));
    }

    /**
     * Imagen como data URI (para incrustarla en PDFs sin depender de URLs públicas).
     */
    public static function dataUri(?string $ruta): ?string
    {
        if (blank($ruta) || ! self::disco()->exists($ruta)) {
            return null;
        }

        $mime = self::disco()->mimeType($ruta) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(self::disco()->get($ruta));
    }
}
