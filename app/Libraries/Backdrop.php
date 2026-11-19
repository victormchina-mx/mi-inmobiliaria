<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Foto de fondo editable desde el admin, con su encuadre y la intensidad del
 * degradado oscuro. Cada fondo es una subclase con sus constantes:
 *   - HomeHero:   portada del inicio ("Déjanos guiarte a tu hogar")
 *   - PageBanner: banner de las páginas interiores (Nosotros, Contacto…)
 *
 * No usa base de datos: la foto se guarda en public/uploads/{DIR}/ y la
 * elección en writable/{CONFIG}. Sin archivo, se usa la foto original del tema.
 *
 * Al subirla se re-codifica con GD: se corrige la rotación de fotos de
 * celular, se reduce a 1920 px de ancho y se guarda en WebP (o JPG), para que
 * la página cargue rápido.
 */
abstract class Backdrop
{
    /** Foto original del tema (relativa a public/) */
    public const DEFAULT = '';
    /** Carpeta dentro de public/uploads/ */
    protected const DIR = '';
    /** Archivo de configuración dentro de writable/ */
    protected const CONFIG = '';
    /** Inicio del nombre de los archivos subidos */
    protected const PREFIX = 'fondo';

    public const MAX_WIDTH = 1920;   // igual que la foto original del tema
    public const MAX_BYTES = 15 * 1024 * 1024;

    /** Encuadre vertical de la foto => background-position */
    public const POSITIONS = [
        'top'    => ['Arriba', 'center top'],
        'center' => ['Centro', 'center center'],
        'bottom' => ['Abajo', 'center bottom'],
    ];

    /** Intensidad del degradado oscuro, en % (100 = como venía la plantilla) */
    public const SHADE_DEFAULT = 100;
    public const SHADE_MIN     = 0;
    public const SHADE_MAX     = 100;

    /** @var array<class-string, array> */
    private static array $cache = [];

    /** @return array{file: ?string, position: string, shade: int} */
    public static function settings(): array
    {
        if (! isset(self::$cache[static::class])) {
            $data = is_file(static::configPath()) ? json_decode((string) file_get_contents(static::configPath()), true) : null;
            $file = is_array($data) && is_string($data['file'] ?? null) ? basename($data['file']) : null;
            if ($file !== null && ! is_file(static::dir() . $file)) {
                $file = null;   // borraron el archivo: vuelve a la original
            }
            $pos         = is_array($data) && isset(self::POSITIONS[$data['position'] ?? '']) ? $data['position'] : 'center';
            $shade       = is_array($data) && isset($data['shade']) ? static::clampShade($data['shade']) : self::SHADE_DEFAULT;
            self::$cache[static::class] = ['file' => $file, 'position' => $pos, 'shade' => $shade];
        }

        return self::$cache[static::class];
    }

    public static function isCustom(): bool
    {
        return static::settings()['file'] !== null;
    }

    /** URL de la foto actual (con ?v= para que el navegador tome la nueva). */
    public static function url(): string
    {
        $file = static::settings()['file'];
        if ($file === null) {
            return base_url(static::DEFAULT);
        }

        return base_url('uploads/' . static::DIR . '/' . $file) . '?v=' . filemtime(static::dir() . $file);
    }

    public static function position(): string
    {
        return static::settings()['position'];
    }

    /** Valor CSS de background-position */
    public static function css(): string
    {
        return self::POSITIONS[static::position()][1];
    }

    /** Intensidad del degradado (0–100 %). */
    public static function shade(): int
    {
        return static::settings()['shade'];
    }

    /** Valor CSS de opacity del degradado ("0.85"). */
    public static function shadeCss(): string
    {
        return rtrim(rtrim(number_format(static::shade() / 100, 2, '.', ''), '0'), '.') ?: '0';
    }

    public static function clampShade($value): int
    {
        $n = (int) round((float) $value);

        return max(self::SHADE_MIN, min(self::SHADE_MAX, $n));
    }

    /**
     * Guarda una foto nueva (con encuadre y degradado). Devuelve el mensaje de error o null.
     */
    public static function upload(UploadedFile $file, string $position, int $shade = self::SHADE_DEFAULT): ?string
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

        if (! is_dir(static::dir())) {
            @mkdir(static::dir(), 0755, true);
        }
        $webp = function_exists('imagewebp');
        $name = static::PREFIX . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . ($webp ? '.webp' : '.jpg');
        $ok   = $webp ? imagewebp($img, static::dir() . $name, 78) : imagejpeg($img, static::dir() . $name, 85);
        imagedestroy($img);
        if (! $ok) {
            return 'No se pudo guardar la imagen en el servidor.';
        }

        $old = static::settings()['file'];
        static::write(['file' => $name, 'position' => $position, 'shade' => $shade]);
        if ($old !== null && $old !== $name) {
            @unlink(static::dir() . $old);
        }

        return null;
    }

    /** Cambia el encuadre y el degradado sin cambiar la foto. */
    public static function setLook(string $position, int $shade): void
    {
        static::write(['file' => static::settings()['file'], 'position' => $position, 'shade' => $shade]);
    }

    /** Vuelve a la foto original del tema, con encuadre y degradado normales (y borra la subida). */
    public static function reset(): void
    {
        $old = static::settings()['file'];
        if (is_file(static::configPath())) {
            @unlink(static::configPath());
        }
        if ($old !== null) {
            @unlink(static::dir() . $old);
        }
        unset(self::$cache[static::class]);
    }

    // ---------------------------------------------------------------

    private static function write(array $data): void
    {
        $data['position'] = isset(self::POSITIONS[$data['position'] ?? '']) ? $data['position'] : 'center';
        $data['shade']    = static::clampShade($data['shade'] ?? self::SHADE_DEFAULT);
        file_put_contents(static::configPath(), json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
        unset(self::$cache[static::class]);
    }

    private static function dir(): string
    {
        return FCPATH . 'uploads/' . static::DIR . '/';
    }

    private static function configPath(): string
    {
        return WRITEPATH . static::CONFIG;
    }
}
