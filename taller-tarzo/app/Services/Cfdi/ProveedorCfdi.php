<?php

namespace App\Services\Cfdi;

use App\Models\Factura;

/**
 * Proveedor Autorizado de Certificación (PAC). Cambiar de PAC = otra implementación y CFDI_DRIVER.
 */
interface ProveedorCfdi
{
    /** Timbra el comprobante. Lanza RuntimeException con el mensaje del PAC/SAT si se rechaza. */
    public function timbrar(Factura $factura): Timbre;

    /**
     * Solicita la cancelación ante el SAT.
     *
     * @return string estado devuelto (cancelada, en_proceso…)
     */
    public function cancelar(Factura $factura, string $motivo, ?string $uuidSustituto = null): string;
}
