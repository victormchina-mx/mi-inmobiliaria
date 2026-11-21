<?php

namespace App\Services\Cfdi;

use App\Models\Factura;

/**
 * Datos del comprobante en una forma neutral que usan los drivers del PAC y el PDF.
 */
final class DatosCfdi
{
    /**
     * @return array<string, mixed>
     */
    public static function de(Factura $f): array
    {
        $f->loadMissing(['conceptos', 'relacionada', 'pago.aplicaciones.factura']);
        $emisor = config('tarzo.cfdi.emisor');

        return [
            'tipo' => $f->tipo,
            'serie' => $f->serie,
            'folio' => (string) $f->folio,
            'fecha' => now()->format('Y-m-d\TH:i:s'),
            'lugar_expedicion' => $emisor['codigo_postal'],
            'moneda' => $f->tipo === Factura::PAGO ? 'XXX' : 'MXN',
            'metodo_pago' => $f->tipo === Factura::PAGO ? null : $f->metodo_pago,
            'forma_pago' => $f->tipo === Factura::PAGO ? null : $f->forma_pago?->value,
            'emisor' => $emisor,
            'receptor' => [
                'rfc' => $f->receptor_rfc,
                'nombre' => $f->receptor_nombre,
                'regimen' => $f->receptor_regimen,
                'codigo_postal' => $f->receptor_cp,
                'uso_cfdi' => $f->tipo === Factura::PAGO ? 'CP01' : $f->uso_cfdi,
            ],
            'relacion' => $f->relacion_tipo && $f->relacionada?->uuid ? ['tipo' => $f->relacion_tipo, 'uuid' => $f->relacionada->uuid] : null,
            'conceptos' => $f->conceptos->map(fn ($c) => [
                'clave_prod_serv' => $c->clave_prod_serv,
                'clave_unidad' => $c->clave_unidad,
                'unidad' => $c->unidad,
                'no_identificacion' => $c->no_identificacion,
                'descripcion' => $c->descripcion,
                'cantidad' => $c->cantidad,
                'valor_unitario' => $c->valor_unitario,
                'importe' => $c->importe,
                'descuento' => $c->descuento,
                'base' => $c->base(),
                'iva_tasa' => $c->iva_tasa,
                'iva' => $c->iva,
            ])->all(),
            'subtotal' => $f->subtotal,
            'descuento' => $f->descuento,
            'iva' => $f->iva,
            'total' => $f->total,
            'pago' => $f->tipo === Factura::PAGO && $f->pago ? self::pago($f) : null,
        ];
    }

    /**
     * Complemento de recepción de pagos 2.0.
     *
     * @return array<string, mixed>
     */
    private static function pago(Factura $f): array
    {
        $p = $f->pago;

        return [
            'fecha' => $p->fecha->format('Y-m-d\TH:i:s'),
            'forma_pago' => $p->forma_pago->value,
            'monto' => $p->aplicaciones->sum('monto'),
            'documentos' => $p->aplicaciones->filter(fn ($a) => $a->factura->metodo_pago === 'PPD')->map(function ($a) {
                $doc = $a->factura;
                $proporcion = $doc->total > 0 ? $a->monto / $doc->total : 0;

                return [
                    'uuid' => $doc->uuid,
                    'serie' => $doc->serie,
                    'folio' => (string) $doc->folio,
                    'parcialidad' => $a->parcialidad,
                    'saldo_anterior' => $a->saldo_anterior,
                    'pagado' => $a->monto,
                    'saldo_insoluto' => round($a->saldo_anterior - $a->monto, 2),
                    'iva_base' => round(($doc->subtotal - $doc->descuento) * $proporcion, 2),
                    'iva' => round($doc->iva * $proporcion, 2),
                ];
            })->values()->all(),
        ];
    }
}
