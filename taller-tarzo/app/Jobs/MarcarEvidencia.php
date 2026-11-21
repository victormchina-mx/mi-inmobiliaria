<?php

namespace App\Jobs;

use App\Models\Evidencia;
use App\Support\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Agrega a la foto una franja con folio, fecha y hora (marca de agua) para respaldo ante la aseguradora.
 */
class MarcarEvidencia implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 40;

    public function __construct(public int $evidenciaId)
    {
        $this->onQueue('baja');
    }

    public function handle(): void
    {
        $evidencia = Evidencia::with('orden')->find($this->evidenciaId);

        if (! $evidencia || $evidencia->marcada_at || ! Media::disco()->exists($evidencia->archivo)) {
            return;
        }

        $original = Media::disco()->get($evidencia->archivo);
        $img = @imagecreatefromstring($original);

        if ($img === false) {
            return; // no es una imagen que GD entienda (p. ej. HEIC): se deja tal cual
        }

        $texto = $evidencia->orden->folio.' · '.$evidencia->created_at->format('d/m/Y H:i').' · '.$evidencia->momento->getLabel();
        self::estampar($img, $texto);

        // Conserva el formato original para que la extensión del archivo siga siendo correcta.
        $tipo = getimagesizefromstring($original)[2] ?? IMAGETYPE_JPEG;
        ob_start();
        match ($tipo) {
            IMAGETYPE_PNG => imagepng($img),
            IMAGETYPE_WEBP => imagewebp($img, null, 85),
            default => imagejpeg($img, null, 85),
        };
        imagedestroy($img);
        Media::disco()->put($evidencia->archivo, ob_get_clean());

        $evidencia->forceFill(['marcada_at' => now()])->saveQuietly();
    }

    public static function estampar(\GdImage $img, string $texto): void
    {
        $ancho = imagesx($img);
        $alto = imagesy($img);
        $franja = max(28, (int) round($alto * 0.06));
        $tamano = max(10, (int) round($franja * 0.45));

        imagefilledrectangle($img, 0, $alto - $franja, $ancho, $alto, imagecolorallocatealpha($img, 0, 0, 0, 50));
        $blanco = imagecolorallocate($img, 255, 255, 255);
        $fuente = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        if (function_exists('imagettftext') && is_file($fuente)) {
            imagettftext($img, $tamano, 0, 12, $alto - (int) round(($franja - $tamano) / 2), $blanco, $fuente, $texto);
        } else {
            imagestring($img, 5, 12, $alto - $franja + 6, iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto, $blanco);
        }
    }
}
