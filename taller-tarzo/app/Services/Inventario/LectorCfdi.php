<?php

namespace App\Services\Inventario;

use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

/**
 * Lee la factura (CFDI 3.3 o 4.0) del proveedor para tomar el costo real de cada concepto.
 * Solo lectura: no valida el sello ni consulta al SAT.
 */
class LectorCfdi
{
    /**
     * @return array{uuid: ?string, rfc_emisor: ?string, nombre_emisor: ?string, fecha: ?string, subtotal: float, total: float,
     *               conceptos: list<array{no_identificacion: ?string, descripcion: string, cantidad: float, valor_unitario: float, importe: float}>}
     */
    public function leer(string $xml): array
    {
        $anterior = libxml_use_internal_errors(true);
        // LIBXML_NONET: nunca descargar recursos externos de un XML subido por terceros.
        $doc = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($anterior);

        if ($doc === false || $doc->getName() !== 'Comprobante') {
            throw ValidationException::withMessages(['factura' => 'El archivo no es un CFDI válido.']);
        }

        $attr = fn (?SimpleXMLElement $n, string $a) => $n !== null && isset($n[$a]) ? (string) $n[$a] : null;
        $uno = fn (string $xpath) => ($r = $doc->xpath($xpath)) ? $r[0] : null;

        $emisor = $uno('//*[local-name()="Emisor"]');
        $timbre = $uno('//*[local-name()="TimbreFiscalDigital"]');

        $conceptos = [];
        foreach ($doc->xpath('//*[local-name()="Conceptos"]/*[local-name()="Concepto"]') ?: [] as $c) {
            $conceptos[] = [
                'no_identificacion' => $attr($c, 'NoIdentificacion'),
                'descripcion' => (string) ($attr($c, 'Descripcion') ?? ''),
                'cantidad' => (float) ($attr($c, 'Cantidad') ?? 0),
                'valor_unitario' => (float) ($attr($c, 'ValorUnitario') ?? 0),
                'importe' => (float) ($attr($c, 'Importe') ?? 0),
            ];
        }

        return [
            'uuid' => $attr($timbre, 'UUID') !== null ? strtoupper($attr($timbre, 'UUID')) : null,
            'rfc_emisor' => $attr($emisor, 'Rfc'),
            'nombre_emisor' => $attr($emisor, 'Nombre'),
            'fecha' => $attr($doc, 'Fecha'),
            'subtotal' => (float) ($attr($doc, 'SubTotal') ?? 0),
            'total' => (float) ($attr($doc, 'Total') ?? 0),
            'conceptos' => $conceptos,
        ];
    }

    /**
     * Busca el concepto de la factura que corresponde a una pieza: primero por número de parte,
     * luego por descripción parecida.
     *
     * @param  list<array<string, mixed>>  $conceptos
     * @return array<string, mixed>|null
     */
    public static function emparejar(array $conceptos, ?string $numeroParte, string $descripcion): ?array
    {
        $norm = fn (?string $s) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $s));

        if (filled($numeroParte)) {
            foreach ($conceptos as $c) {
                if ($norm($c['no_identificacion']) === $norm($numeroParte) || str_contains($norm($c['descripcion']), $norm($numeroParte))) {
                    return $c;
                }
            }
        }

        $mejor = null;
        $mejorPct = 0;
        foreach ($conceptos as $c) {
            similar_text(mb_strtolower($c['descripcion']), mb_strtolower($descripcion), $pct);
            if ($pct > $mejorPct) {
                [$mejor, $mejorPct] = [$c, $pct];
            }
        }

        return $mejorPct >= 60 ? $mejor : null;
    }
}
