<?php

namespace App\Actions\Facturacion;

use App\Models\Aseguradora;
use App\Models\Cliente;
use Illuminate\Validation\ValidationException;

/**
 * Datos fiscales del receptor para el CFDI 4.0 (RFC, nombre, régimen y código postal deben coincidir con la constancia).
 */
final class Receptor
{
    /**
     * @return array<string, mixed>
     */
    public static function cliente(Cliente $cliente): array
    {
        $df = $cliente->datosFiscales;
        if (! $df) {
            throw ValidationException::withMessages(['receptor' => "Captura los datos fiscales de {$cliente->nombre_completo} (RFC, nombre, régimen y código postal) antes de facturar."]);
        }

        return [
            'receptor_tipo' => 'cliente', 'cliente_id' => $cliente->id, 'aseguradora_id' => null,
            'receptor_rfc' => $df->rfc, 'receptor_nombre' => $df->nombre_fiscal, 'receptor_regimen' => $df->regimen_fiscal,
            'receptor_cp' => $df->codigo_postal, 'uso_cfdi' => $df->uso_cfdi ?: 'G03',
            'email' => $df->email_facturacion ?: $cliente->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function aseguradora(Aseguradora $a): array
    {
        if (blank($a->rfc) || blank($a->razon_social) || blank($a->regimen_fiscal) || blank($a->codigo_postal)) {
            throw ValidationException::withMessages(['receptor' => "Completa los datos fiscales de {$a->nombre} en Catálogos → Aseguradoras (RFC, razón social, régimen y código postal)."]);
        }

        return [
            'receptor_tipo' => 'aseguradora', 'cliente_id' => null, 'aseguradora_id' => $a->id,
            'receptor_rfc' => $a->rfc, 'receptor_nombre' => $a->razon_social, 'receptor_regimen' => $a->regimen_fiscal,
            'receptor_cp' => $a->codigo_postal, 'uso_cfdi' => $a->uso_cfdi ?: 'G03',
            'email' => $a->email_facturacion ?: $a->email,
        ];
    }
}
