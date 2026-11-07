<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Versiones reducidas (miniaturas) de las fotos subidas, en WebP.
 *
 * URL: /img/{carpeta}/{ancho}/{archivo}.webp   p. ej. /img/property/800/123_abc.jpg.webp
 *
 * La primera vez se genera con GD a partir de public/uploads/{carpeta}/{archivo}
 * y se guarda en public/img/{carpeta}/{ancho}/{archivo}.webp, que es la misma
 * ruta que la URL: desde la segunda vez el servidor web entrega el archivo
 * directamente, sin pasar por PHP.
 *
 * Solo se aceptan las carpetas y anchos de abajo, así nadie puede llenar el
 * disco pidiendo tamaños arbitrarios. Si algo falla, redirige a la original.
 * Vista auxiliar: thumb_url() en app/Common.php.
 */
class Thumb extends BaseController
{
    public const FOLDERS = ['property', 'user', 'about'];
    public const WIDTHS  = [200, 400, 800, 1600];

    private const QUALITY    = 78;
    private const MAX_PIXELS = 40_000_000; // ~ 7300×5500: más grande no se procesa

    public function show(string $folder, string $width, string $file): ResponseInterface
    {
        $width = (int) $width;

        if (! in_array($folder, self::FOLDERS, true) || ! in_array($width, self::WIDTHS, true)
            || ! preg_match('/^([A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp))\.(webp|jpg)$/i', $file, $m)) {
            return $this->response->setStatusCode(404);
        }

        [, $original, $format] = $m;
        $format = strtolower($format);
        $source = FCPATH . 'uploads/' . $folder . '/' . $original;
        $fallback = redirect()->to(base_url('uploads/' . $folder . '/' . $original));

        if (! is_file($source)) {
            return $this->response->setStatusCode(404);
        }

        $info = @getimagesize($source);
        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS || ! function_exists('imagecreatefromstring')) {
            return $fallback;
        }

        $image = @imagecreatefromstring((string) file_get_contents($source));
        if (! $image) {
            return $fallback;
        }

        $image = $this->fixOrientation($image, $source, $info[2]);

        // Solo se reduce, nunca se agranda
        if (imagesx($image) > $width) {
            $scaled = $this->resize($image, $width);
            if ($scaled === null) {
                imagedestroy($image);

                return $fallback;
            }
            imagedestroy($image);
            $image = $scaled;
        }

        ob_start();
        if ($format === 'webp' && function_exists('imagewebp')) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            $ok   = imagewebp($image, null, self::QUALITY);
            $mime = 'image/webp';
        } else {
            // Sin WebP en el servidor: JPEG (con fondo blanco si había transparencia)
            $ok   = imagejpeg($this->flatten($image), null, self::QUALITY);
            $mime = 'image/jpeg';
        }
        $data = (string) ob_get_clean();
        imagedestroy($image);

        if (! $ok || $data === '') {
            return $fallback;
        }

        // Guardar para que la próxima vez lo sirva el servidor web directamente
        $dir = FCPATH . 'img/' . $folder . '/' . $width;
        if (is_dir($dir) || @mkdir($dir, 0755, true)) {
            @file_put_contents($dir . '/' . $file, $data, LOCK_EX);
        }

        return $this->response
            ->setStatusCode(200)
            ->setContentType($mime)
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->setBody($data);
    }

    /**
     * Reduce a $width de ancho conservando la proporción.
     *
     * Se usa imagecopyresampled (disponible en cualquier GD): imagescale() con
     * IMG_BICUBIC devolvía la imagen sin reducir en el GD de Hostinger, y las
     * "miniaturas" de 800 px salían de 3840 px.
     */
    private function resize(\GdImage $image, int $width): ?\GdImage
    {
        $srcW   = imagesx($image);
        $srcH   = imagesy($image);
        $height = max(1, (int) round($srcH * $width / $srcW));

        $dest = imagecreatetruecolor($width, $height);
        if ($dest === false) {
            return null;
        }

        // Conservar la transparencia de PNG/GIF
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127));

        if (! imagecopyresampled($dest, $image, 0, 0, 0, 0, $width, $height, $srcW, $srcH) || imagesx($dest) !== $width) {
            imagedestroy($dest);

            return null;
        }

        return $dest;
    }

    /** Las fotos del celular guardan su giro en EXIF; GD lo ignora y saldrían de lado. */
    private function fixOrientation(\GdImage $image, string $source, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif  = @exif_read_data($source);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function flatten(\GdImage $image): \GdImage
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $flat;
    }
}
