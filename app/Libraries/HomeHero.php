<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Foto de fondo de la portada del inicio ("Déjanos guiarte a tu hogar").
 *
 * Se cambia en Admin → Portada del inicio (Admin\HomeHero). No usa base de
 * datos: la foto se guarda en public/uploads/home/ y la elección en
 * writable/home-hero.json. Sin archivo, se usa la foto original del tema.
 *
 * Al subirla se re-codifica con GD: se corrige la rotación de fotos de
 * celular, se reduce a 1920 px de ancho y se guarda en WebP (o JPG), para que
 * la portada cargue rápido.
 */
class HomeHero
{
    public const DEFAULT   = 'images/banner/rshmpg.webp';
    public const MAX_WIDTH = 1920;   // igual que la foto original del tema
    public const MAX_BYTES = 15 * 1024 * 1024;

    /** Encuadre vertical de la foto => background-position */
    public const POSITIONS = [
        'top'    => ['Arriba', 'center top'],
        'center' => ['Centro', 'center center'],
        'bottom' => ['Abajo', 'center bottom'],
    ];

    private static ?array $cache = null;

    /** @return array{file: ?string, position: string} */
    public static function settings(): array
    {
        if (self::$cache === null) {
            $data = is_file(self::configPath()) ? json_decode((string) file_get_contents(self::configPath()), true) : null;
            $file = is_array($data) && is_string($data['file'] ?? null) ? basename($data['file']) : null;
            if ($file !== null && ! is_file(self::dir() . $file)) {
                $file = null;   // borraron el archivo: vuelve a la original
            }
            $pos         = is_array($data) && isset(self::POSITIONS[$data['position'] ?? '']) ? $data['position'] : 'center';
            self::$cache = ['file' => $file, 'position' => $pos];
        }

        return self::$cache;
    }

    public static function isCustom(): bool
    {
        return self::settings()['file'] !== null;
    }

    /** URL de la foto actual (con ?v= para que el navegador tome la nueva). */
    public static function url(): string
    {
        $file = self::settings()['file'];
        if ($file === null) {
            return base_url(self::DEFAULT);
        }

        return base_url('uploads/home/' . $file) . '?v=' . filemtime(self::dir() . $file);
    }

    public static function position(): string
    {
        return self::settings()['position'];
    }

    /** Valor CSS de background-position */
    public static function css(): string
    {
        return self::POSITIONS[self::position()][1];
    }

    /**
     * Guarda una foto nueva (y la posición). Devuelve el mensaje de error o null.
     */
    public static function upload(UploadedFile $file, string $position): ?string
    {
        if (! $file->isValid()) {
            return $file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE
                ? 'La imagen pesa demasiado para el servidor. Usa una de menos de 15 MB.'
                : 'No se pudo subir la imagen. Intenta de nuevo.';
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return 'La imagen pesa más de 15 MB.';
        }
        $types = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $kind  = $types[$file->getMimeType()] ?? null;
        $info  = @getimagesize($file->getTempName());
        if (! $kind || ! $info) {
            return 'Solo se permiten imágenes JPG, PNG o WebP.';
        }
        if ($info[0] < 1200 || $info[1] < 600) {
            return 'La imagen es muy pequeña (' . $info[0] . '×' . $info[1] . ' px). Usa una de al menos 1200 px de ancho; lo ideal es 1920×1080 o más.';
        }
        if (! function_exists('imagecreatefromjpeg')) {
            return 'El servidor no puede procesar imágenes (falta GD).';
        }

        @ini_set('memory_limit', '512M');   // fotos grandes de celular
        $img = match ($kind) {
            'jpeg'  => @imagecreatefromjpeg($file->getTempName()),
            'png'   => @imagecreatefrompng($file->getTempName()),
            default => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getTempName()) : false,
        };
        if (! $img) {
            return 'No se pudo leer la imagen. Prueba guardarla de nuevo como JPG.';
        }

        // Fotos de celular: girarlas según la orientación EXIF
        if ($kind === 'jpeg' && function_exists('exif_read_data')) {
            $o   = (int) (@exif_read_data($file->getTempName())['Orientation'] ?? 1);
            $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($rot) {
                $turned = imagerotate($img, $rot, 0);
                if ($turned) {
                    imagedestroy($img);
                    $img = $turned;
                }
            }
        }

        if (imagesx($img) > self::MAX_WIDTH) {
            $small = imagescale($img, self::MAX_WIDTH, -1, IMG_BICUBIC);
            if ($small) {
                imagedestroy($img);
                $img = $small;
            }
        }

        if (! is_dir(self::dir())) {
            @mkdir(self::dir(), 0755, true);
        }
        $webp = function_exists('imagewebp');
        $name = 'portada-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . ($webp ? '.webp' : '.jpg');
        $ok   = $webp ? imagewebp($img, self::dir() . $name, 78) : imagejpeg($img, self::dir() . $name, 85);
        imagedestroy($img);
        if (! $ok) {
            return 'No se pudo guardar la imagen en el servidor.';
        }

        $old = self::settings()['file'];
        self::write(['file' => $name, 'position' => $position]);
        if ($old !== null && $old !== $name) {
            @unlink(self::dir() . $old);
        }

        return null;
    }

    /** Cambia solo el encuadre. */
    public static function setPosition(string $position): void
    {
        self::write(['file' => self::settings()['file'], 'position' => $position]);
    }

    /** Vuelve a la foto original del tema (y borra la subida). */
    public static function reset(): void
    {
        $old = self::settings()['file'];
        if (is_file(self::configPath())) {
            @unlink(self::configPath());
        }
        if ($old !== null) {
            @unlink(self::dir() . $old);
        }
        self::$cache = null;
    }

    // ---------------------------------------------------------------

    private static function write(array $data): void
    {
        $data['position'] = isset(self::POSITIONS[$data['position'] ?? '']) ? $data['position'] : 'center';
        file_put_contents(self::configPath(), json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
        self::$cache = null;
    }

    private static function dir(): string
    {
        return FCPATH . 'uploads/home/';
    }

    private static function configPath(): string
    {
        return WRITEPATH . 'home-hero.json';
    }
}
