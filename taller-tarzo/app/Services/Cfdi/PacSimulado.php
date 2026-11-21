<?php

namespace App\Services\Cfdi;

use App\Models\Factura;
use Illuminate\Support\Str;

/**
 * Timbrado simulado para desarrollo y capacitación: XML con la estructura del CFDI 4.0 y un
 * TimbreFiscalDigital ficticio. NO tiene validez fiscal (no lleva sello ni certificado del SAT).
 */
class PacSimulado implements ProveedorCfdi
{
    public function timbrar(Factura $factura): Timbre
    {
        $uuid = strtoupper((string) Str::uuid());

        return new Timbre($uuid, 'sim-'.$factura->id, (new GeneradorXml)->generar(DatosCfdi::de($factura), $uuid));
    }

    public function cancelar(Factura $factura, string $motivo, ?string $uuidSustituto = null): string
    {
        return 'cancelada';
    }
}
