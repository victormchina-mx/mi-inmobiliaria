<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Acceso único al disco privado de fotos, video, firmas y documentos (config tarzo.media.disco).
 *
 * En disco local los archivos se entregan por /archivo/{clave}?expires=…&signature=…, una dirección
 * SIN extensión: LiteSpeed (Hostinger) trata como estático lo que termina en .jpg y, al no encontrarlo
 * en disco, lo pasa a Laravel sin la firma, que entonces responde 404. En S3/R2 se usa su URL temporal.
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

    public static function esLocal(): bool
    {
        return config('filesystems.disks.'.self::nombreDisco().'.driver') === 'local';
    }

    /**
     * Hace que temporaryUrl() del disco (vistas previas de Filament) use el mismo enlace sin extensión.
     * Se llama al arrancar la app (AppServiceProvider).
     */
    public static function configurarDisco(): void
    {
        if (self::esLocal()) {
            Storage::disk(self::nombreDisco())->buildTemporaryUrlsUsing(fn (string $ruta, \DateTimeInterface $vence) => Media::urlFirmada($ruta, $vence)); // Laravel re-enlaza el closure: no usar self::
        }
    }

    public static function urlTemporal(?string $ruta, int $minutos = 30): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        return self::esLocal()
            ? self::urlFirmada($ruta, now()->addMinutes($minutos))
            : self::disco()->temporaryUrl($ruta, now()->addMinutes($minutos));
    }

    public static function urlFirmada(string $ruta, \DateTimeInterface $vence): string
    {
        return url(URL::temporarySignedRoute('archivo.ver', $vence, ['clave' => self::codificar($ruta)], absolute: false));
    }

    public static function codificar(string $ruta): string
    {
        return rtrim(strtr(base64_encode($ruta), '+/', '-_'), '=');
    }

    public static function decodificar(string $clave): ?string
    {
        $ruta = base64_decode(strtr($clave, '-_', '+/'), true);

        return is_string($ruta) && $ruta !== '' && ! str_contains($ruta, '..') && ! str_starts_with($ruta, '/') ? $ruta : null;
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
