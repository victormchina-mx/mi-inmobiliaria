<?php

namespace App\Services\Cfdi;

use DOMDocument;
use DOMElement;

/**
 * Arma el XML de un CFDI 4.0 a partir de DatosCfdi (usado por el PAC simulado).
 */
class GeneradorXml
{
    private const NS = 'http://www.sat.gob.mx/cfd/4';

    private const NS_TFD = 'http://www.sat.gob.mx/TimbreFiscalDigital';

    private const NS_PAGO = 'http://www.sat.gob.mx/Pagos20';

    private DOMDocument $doc;

    public function generar(array $d, string $uuid): string
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;

        $esPago = $d['tipo'] === 'P';
        $c = $this->el('cfdi:Comprobante', array_filter([
            'Version' => '4.0', 'Serie' => $d['serie'], 'Folio' => $d['folio'], 'Fecha' => $d['fecha'],
            'FormaPago' => $d['forma_pago'], 'SubTotal' => $esPago ? '0' : $this->m($d['subtotal']),
            'Descuento' => ! $esPago && $d['descuento'] > 0 ? $this->m($d['descuento']) : null,
            'Moneda' => $d['moneda'], 'Total' => $esPago ? '0' : $this->m($d['total']),
            'TipoDeComprobante' => $d['tipo'], 'Exportacion' => '01', 'MetodoPago' => $d['metodo_pago'],
            'LugarExpedicion' => $d['lugar_expedicion'],
        ], fn ($v) => $v !== null));
        $c->setAttribute('xmlns:cfdi', self::NS);
        $this->doc->appendChild($c);

        if ($d['relacion']) {
            $r = $c->appendChild($this->el('cfdi:CfdiRelacionados', ['TipoRelacion' => $d['relacion']['tipo']]));
            $r->appendChild($this->el('cfdi:CfdiRelacionado', ['UUID' => $d['relacion']['uuid']]));
        }

        $c->appendChild($this->el('cfdi:Emisor', ['Rfc' => $d['emisor']['rfc'], 'Nombre' => $d['emisor']['nombre'], 'RegimenFiscal' => $d['emisor']['regimen']]));
        $c->appendChild($this->el('cfdi:Receptor', [
            'Rfc' => $d['receptor']['rfc'], 'Nombre' => $d['receptor']['nombre'], 'DomicilioFiscalReceptor' => $d['receptor']['codigo_postal'],
            'RegimenFiscalReceptor' => $d['receptor']['regimen'], 'UsoCFDI' => $d['receptor']['uso_cfdi'],
        ]));

        $conceptos = $c->appendChild($this->el('cfdi:Conceptos'));
        if ($esPago) {
            $conceptos->appendChild($this->el('cfdi:Concepto', ['ClaveProdServ' => '84111506', 'Cantidad' => '1', 'ClaveUnidad' => 'ACT', 'Descripcion' => 'Pago', 'ValorUnitario' => '0', 'Importe' => '0', 'ObjetoImp' => '01']));
        }
        foreach ($d['conceptos'] as $x) {
            $con = $conceptos->appendChild($this->el('cfdi:Concepto', array_filter([
                'ClaveProdServ' => $x['clave_prod_serv'], 'NoIdentificacion' => $x['no_identificacion'], 'Cantidad' => (string) $x['cantidad'],
                'ClaveUnidad' => $x['clave_unidad'], 'Unidad' => $x['unidad'], 'Descripcion' => $x['descripcion'],
                'ValorUnitario' => $this->m($x['valor_unitario'], 4), 'Importe' => $this->m($x['importe']),
                'Descuento' => $x['descuento'] > 0 ? $this->m($x['descuento']) : null, 'ObjetoImp' => '02',
            ], fn ($v) => $v !== null && $v !== '')));
            $tras = $con->appendChild($this->el('cfdi:Impuestos'))->appendChild($this->el('cfdi:Traslados'));
            $tras->appendChild($this->el('cfdi:Traslado', ['Base' => $this->m($x['base']), 'Impuesto' => '002', 'TipoFactor' => 'Tasa', 'TasaOCuota' => $this->m($x['iva_tasa'], 6), 'Importe' => $this->m($x['iva'])]));
        }

        if (! $esPago && $d['conceptos']) {
            $imp = $c->appendChild($this->el('cfdi:Impuestos', ['TotalImpuestosTrasladados' => $this->m($d['iva'])]));
            $imp->appendChild($this->el('cfdi:Traslados'))->appendChild($this->el('cfdi:Traslado', [
                'Base' => $this->m($d['subtotal'] - $d['descuento']), 'Impuesto' => '002', 'TipoFactor' => 'Tasa', 'TasaOCuota' => '0.160000', 'Importe' => $this->m($d['iva']),
            ]));
        }

        $complemento = $c->appendChild($this->el('cfdi:Complemento'));
        if ($esPago && $d['pago']) {
            $complemento->appendChild($this->pagos($d['pago']));
        }
        $tfd = $this->doc->createElementNS(self::NS_TFD, 'tfd:TimbreFiscalDigital');
        foreach (['Version' => '1.1', 'UUID' => $uuid, 'FechaTimbrado' => now()->format('Y-m-d\TH:i:s'), 'RfcProvCertif' => 'SPR190613I52', 'SelloCFD' => 'SIMULADO', 'NoCertificadoSAT' => '00000000000000000000', 'SelloSAT' => 'SIMULADO-SIN-VALIDEZ-FISCAL'] as $k => $v) {
            $tfd->setAttribute($k, $v);
        }
        $complemento->appendChild($tfd);

        return $this->doc->saveXML();
    }

    private function pagos(array $p): DOMElement
    {
        $pagos = $this->doc->createElementNS(self::NS_PAGO, 'pago20:Pagos');
        $pagos->setAttribute('Version', '2.0');
        $iva = array_sum(array_column($p['documentos'], 'iva'));
        $base = array_sum(array_column($p['documentos'], 'iva_base'));
        $pagos->appendChild($this->el('pago20:Totales', ['TotalTrasladosBaseIVA16' => $this->m($base), 'TotalTrasladosImpuestoIVA16' => $this->m($iva), 'MontoTotalPagos' => $this->m($p['monto'])], self::NS_PAGO));
        $pago = $pagos->appendChild($this->el('pago20:Pago', ['FechaPago' => $p['fecha'], 'FormaDePagoP' => $p['forma_pago'], 'MonedaP' => 'MXN', 'TipoCambioP' => '1', 'Monto' => $this->m($p['monto'])], self::NS_PAGO));
        foreach ($p['documentos'] as $doc) {
            $dr = $pago->appendChild($this->el('pago20:DoctoRelacionado', [
                'IdDocumento' => $doc['uuid'], 'Serie' => $doc['serie'], 'Folio' => $doc['folio'], 'MonedaDR' => 'MXN', 'EquivalenciaDR' => '1',
                'NumParcialidad' => (string) $doc['parcialidad'], 'ImpSaldoAnt' => $this->m($doc['saldo_anterior']), 'ImpPagado' => $this->m($doc['pagado']),
                'ImpSaldoInsoluto' => $this->m($doc['saldo_insoluto']), 'ObjetoImpDR' => '02',
            ], self::NS_PAGO));
            $dr->appendChild($this->el('pago20:ImpuestosDR', [], self::NS_PAGO))->appendChild($this->el('pago20:TrasladosDR', [], self::NS_PAGO))
                ->appendChild($this->el('pago20:TrasladoDR', ['BaseDR' => $this->m($doc['iva_base']), 'ImpuestoDR' => '002', 'TipoFactorDR' => 'Tasa', 'TasaOCuotaDR' => '0.160000', 'ImporteDR' => $this->m($doc['iva'])], self::NS_PAGO));
        }

        return $pagos;
    }

    private function el(string $nombre, array $atributos = [], string $ns = self::NS): DOMElement
    {
        $e = $this->doc->createElementNS($ns, $nombre);
        foreach ($atributos as $k => $v) {
            $e->setAttribute($k, (string) $v);
        }

        return $e;
    }

    private function m(float|int|string|null $v, int $dec = 2): string
    {
        return number_format((float) $v, $dec, '.', '');
    }
}
