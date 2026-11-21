<?php

namespace App\Services\Cfdi;

use App\Models\Factura;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Facturama API Lite (multiemisor): el CSD del emisor se sube una vez en su portal.
 * Pruebas: https://apisandbox.facturama.mx · Producción: https://api.facturama.mx
 * Antes de producción, validar en sandbox: ingreso PUE, PPD + REP, nota de crédito y cancelación.
 */
class PacFacturama implements ProveedorCfdi
{
    /** Catálogo NameId de Facturama (nombre del documento en su representación impresa). */
    private const NAME_ID = ['I' => '1', 'E' => '2', 'P' => '14'];

    public function timbrar(Factura $factura): Timbre
    {
        $r = $this->http()->post('/api-lite/3/cfdis', $this->payload(DatosCfdi::de($factura)));
        $this->validar($r);

        $id = (string) $r->json('Id');
        $uuid = (string) $r->json('Complement.TaxStamp.Uuid');
        if ($id === '' || $uuid === '') {
            throw new RuntimeException('Facturama no devolvió el UUID del timbrado.');
        }

        return new Timbre($uuid, $id, $this->descargar('xml', $id), $this->descargar('pdf', $id));
    }

    public function cancelar(Factura $factura, string $motivo, ?string $uuidSustituto = null): string
    {
        $r = $this->http()->delete('/api-lite/cfdis/'.$factura->pac_id.'?'.http_build_query(array_filter([
            'motive' => $motivo,
            'uuidReplacement' => $uuidSustituto,
        ])));
        $this->validar($r);

        // "canceled", "pending" (el receptor debe aceptar) o "active" si fue rechazada.
        return match (strtolower((string) $r->json('Status'))) {
            'canceled', 'cancelado' => 'cancelada',
            'pending', 'inprocess', 'en proceso' => 'en_proceso',
            default => (string) ($r->json('Status') ?: 'cancelada'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(array $d): array
    {
        $p = array_filter([
            'NameId' => self::NAME_ID[$d['tipo']],
            'Serie' => $d['serie'],
            'Folio' => $d['folio'],
            'CfdiType' => $d['tipo'],
            'Currency' => $d['moneda'],
            'ExpeditionPlace' => $d['lugar_expedicion'],
            'PaymentForm' => $d['forma_pago'],
            'PaymentMethod' => $d['metodo_pago'],
            'Exportation' => '01',
            'Issuer' => ['Rfc' => $d['emisor']['rfc'], 'Name' => $d['emisor']['nombre'], 'FiscalRegime' => $d['emisor']['regimen']],
            'Receiver' => [
                'Rfc' => $d['receptor']['rfc'], 'Name' => $d['receptor']['nombre'], 'CfdiUse' => $d['receptor']['uso_cfdi'],
                'FiscalRegime' => $d['receptor']['regimen'], 'TaxZipCode' => $d['receptor']['codigo_postal'],
            ],
            'Relations' => $d['relacion'] ? ['Type' => $d['relacion']['tipo'], 'Cfdis' => [['Uuid' => $d['relacion']['uuid']]]] : null,
        ], fn ($v) => $v !== null);

        if ($d['tipo'] === 'P') {
            $pago = $d['pago'];
            $p['Complemento'] = ['Payments' => [[
                'Date' => $pago['fecha'],
                'PaymentForm' => $pago['forma_pago'],
                'Currency' => 'MXN',
                'Amount' => $pago['monto'],
                'RelatedDocuments' => array_map(fn ($doc) => [
                    'TaxObject' => '02',
                    'Uuid' => $doc['uuid'],
                    'Serie' => $doc['serie'],
                    'Folio' => $doc['folio'],
                    'Currency' => 'MXN',
                    'PaymentMethod' => 'PPD',
                    'PartialityNumber' => $doc['parcialidad'],
                    'PreviousBalanceAmount' => $doc['saldo_anterior'],
                    'AmountPaid' => $doc['pagado'],
                    'ImpSaldoInsoluto' => $doc['saldo_insoluto'],
                    'Taxes' => [['Name' => 'IVA', 'Rate' => 0.16, 'Total' => $doc['iva'], 'Base' => $doc['iva_base'], 'IsRetention' => false]],
                ], $pago['documentos']),
            ]]];

            return $p;
        }

        $p['Items'] = array_map(fn ($c) => array_filter([
            'ProductCode' => $c['clave_prod_serv'],
            'IdentificationNumber' => $c['no_identificacion'],
            'Description' => $c['descripcion'],
            'Unit' => $c['unidad'],
            'UnitCode' => $c['clave_unidad'],
            'UnitPrice' => round($c['valor_unitario'], 4),
            'Quantity' => $c['cantidad'],
            'Subtotal' => $c['importe'],
            'Discount' => $c['descuento'] > 0 ? $c['descuento'] : null,
            'TaxObject' => '02',
            'Taxes' => [['Total' => $c['iva'], 'Name' => 'IVA', 'Base' => $c['base'], 'Rate' => $c['iva_tasa'], 'IsRetention' => false]],
            'Total' => round($c['base'] + $c['iva'], 2),
        ], fn ($v) => $v !== null && $v !== ''), $d['conceptos']);

        return $p;
    }

    private function descargar(string $formato, string $id): string
    {
        $r = $this->http()->get("/cfdi/{$formato}/issuedLite/{$id}");
        $this->validar($r);

        return (string) base64_decode((string) $r->json('Content'), true);
    }

    private function http(): PendingRequest
    {
        $c = config('tarzo.cfdi');
        if (blank($c['usuario']) || blank($c['password'])) {
            throw new RuntimeException('Facturación no configurada: faltan CFDI_USUARIO y CFDI_PASSWORD.');
        }

        $base = $c['ambiente'] === 'produccion' ? 'https://api.facturama.mx' : 'https://apisandbox.facturama.mx';

        return Http::baseUrl($base)->withBasicAuth($c['usuario'], $c['password'])->acceptJson()->timeout(30)->retry(2, 500, throw: false);
    }

    private function validar(Response $r): void
    {
        if ($r->successful()) {
            return;
        }

        $detalle = collect($r->json('ModelState') ?? [])->flatten()->implode(' ') ?: ($r->json('Message') ?? mb_substr($r->body(), 0, 300));

        throw new RuntimeException("El PAC rechazó el comprobante ({$r->status()}): {$detalle}");
    }
}
