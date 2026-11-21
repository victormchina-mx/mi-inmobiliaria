<?php

namespace App\Actions\Recepcion;

use App\Jobs\GenerarPdfIngreso;
use App\Models\Checkin;
use App\Support\Media;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Guarda la firma del cliente sobre el check-in y deja la evidencia: quién, cuándo,
 * desde dónde y la huella del contenido firmado. Después genera el PDF de ingreso.
 */
class FirmarCheckin
{
    public function __invoke(
        Checkin $checkin,
        string $firmaDataUri,
        string $nombre,
        bool $aceptaAvisoPrivacidad,
        bool $aceptaCondiciones,
        string $medio,
        ?string $ip = null,
        ?string $dispositivo = null,
    ): Checkin {
        if ($checkin->estaFirmado()) {
            throw ValidationException::withMessages(['firma' => 'Este inventario ya fue firmado.']);
        }

        if (! $aceptaAvisoPrivacidad || ! $aceptaCondiciones) {
            throw ValidationException::withMessages(['acepta' => 'Para firmar se deben aceptar el aviso de privacidad y las condiciones del servicio.']);
        }

        if (mb_strlen(trim($nombre)) < 3) {
            throw ValidationException::withMessages(['nombre' => 'Escribe el nombre completo de quien firma.']);
        }

        $png = $this->decodificarPng($firmaDataUri);
        $ruta = "firmas/{$checkin->orden->folio}-".Str::random(8).'.png';
        Media::disco()->put($ruta, $png);

        $checkin->forceFill([
            'firma' => $ruta,
            'firmado_por' => trim($nombre),
            'firmado_at' => now(),
            'firma_ip' => $ip,
            'firma_dispositivo' => Str::limit((string) $dispositivo, 250, ''),
            'firma_medio' => $medio,
            'firma_huella' => $checkin->calcularHuella(),
            'acepta_aviso_privacidad' => true,
            'acepta_condiciones' => true,
            'completado_at' => $checkin->completado_at ?? now(),
        ])->save();

        GenerarPdfIngreso::dispatch($checkin->id, enviarAlCliente: true);

        return $checkin;
    }

    private function decodificarPng(string $dataUri): string
    {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUri, $m)) {
            throw ValidationException::withMessages(['firma' => 'La firma no es válida. Vuelve a firmar.']);
        }

        $binario = base64_decode($m[1], true);

        // Una firma real pesa entre 2 KB y ~300 KB; un lienzo vacío pesa menos de 1 KB.
        if ($binario === false || strlen($binario) < 1500 || strlen($binario) > 1_000_000 || ! str_starts_with($binario, "\x89PNG")) {
            throw ValidationException::withMessages(['firma' => 'La firma está vacía o no es válida. Firma dentro del recuadro.']);
        }

        return $binario;
    }
}
