<?php

namespace App\Services\Cfdi;

use App\Enums\EstadoFactura;
use App\Models\Factura;
use App\Services\Finanzas\Rentabilidad;
use App\Support\Media;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Timbra y cancela facturas con el PAC configurado, guarda XML y PDF y mantiene los saldos.
 */
class Facturador
{
    public function __construct(private ProveedorCfdi $pac) {}

    public function timbrar(Factura $f): Factura
    {
        if (! $f->editable()) {
            throw ValidationException::withMessages(['factura' => 'Esta factura ya fue timbrada o cancelada.']);
        }

        $f->loadMissing('conceptos');
        if ($f->tipo !== Factura::PAGO && $f->conceptos->isEmpty()) {
            throw ValidationException::withMessages(['factura' => 'La factura no tiene conceptos.']);
        }

        try {
            $timbre = $this->pac->timbrar($f);
        } catch (Throwable $e) {
            $f->forceFill(['estado' => EstadoFactura::Error, 'error' => mb_substr($e->getMessage(), 0, 2000)])->save();

            throw ValidationException::withMessages(['factura' => $e->getMessage()]);
        }

        $f = DB::transaction(function () use ($f, $timbre) {
            $base = 'cfdi/'.now()->format('Y/m').'/'.$f->serie.'-'.$f->folio.'-'.substr($timbre->uuid, 0, 8);
            Media::disco()->put("{$base}.xml", $timbre->xml);

            $f->forceFill([
                'estado' => EstadoFactura::Timbrada, 'uuid' => $timbre->uuid, 'pac_id' => $timbre->pacId, 'xml_path' => "{$base}.xml",
                'timbrada_at' => now(), 'error' => null, 'saldo' => $f->tipo === Factura::INGRESO ? $f->total : 0,
            ])->save();

            Media::disco()->put("{$base}.pdf", $timbre->pdf ?: $this->pdf($f));
            $f->forceFill(['pdf_path' => "{$base}.pdf"])->save();

            // Una nota de crédito reduce lo que se debe de la factura original.
            if ($f->tipo === Factura::EGRESO && $f->relacionada) {
                $f->relacionada->forceFill(['saldo' => max(0, round($f->relacionada->saldo - $f->total, 2))])->save();
            }

            return $f;
        });

        $this->actualizarCierre($f);

        return $f;
    }

    public function cancelar(Factura $f, string $motivo, ?Factura $sustituta = null): Factura
    {
        if (! $f->timbrada()) {
            throw ValidationException::withMessages(['factura' => 'Solo se cancelan facturas timbradas.']);
        }
        if ($motivo === '01' && ! $sustituta?->timbrada()) {
            throw ValidationException::withMessages(['sustituta' => 'Con el motivo 01 primero timbra la factura que la sustituye.']);
        }
        if ($f->tipo === Factura::INGRESO && $f->aplicaciones()->exists()) {
            throw ValidationException::withMessages(['factura' => 'La factura tiene pagos aplicados: cancela primero esos pagos (y su complemento).']);
        }
        if ($f->tipo === Factura::INGRESO && $f->notasCredito()->where('estado', EstadoFactura::Timbrada->value)->exists()) {
            throw ValidationException::withMessages(['factura' => 'La factura tiene notas de crédito vigentes: cancélalas primero.']);
        }

        try {
            $estado = $this->pac->cancelar($f, $motivo, $sustituta?->uuid);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['factura' => $e->getMessage()]);
        }

        return DB::transaction(function () use ($f, $motivo, $estado) {
            $f->forceFill(['motivo_cancelacion' => $motivo, 'estado_cancelacion' => $estado])->save();

            if ($estado !== 'cancelada') {
                return $f; // el receptor debe aceptarla; se vuelve a intentar más tarde
            }

            if ($f->tipo === Factura::EGRESO && $f->relacionada) {
                $f->relacionada->forceFill(['saldo' => round(min($f->relacionada->total, $f->relacionada->saldo + $f->total), 2)])->save();
            }

            $f->forceFill(['estado' => EstadoFactura::Cancelada, 'cancelada_at' => now(), 'saldo' => 0])->save();
            $this->actualizarCierre($f);

            return $f;
        });
    }

    /** Si la orden ya se cerró, la utilidad se recalcula con el ingreso facturado real. */
    private function actualizarCierre(Factura $f): void
    {
        if ($f->orden && $f->tipo !== Factura::PAGO && $f->orden->cierre()->exists()) {
            app(Rentabilidad::class)->cerrar($f->orden);
        }
    }

    /**
     * Representación impresa (PDF) con los datos del timbre y el código QR de verificación del SAT.
     */
    public function pdf(Factura $f): string
    {
        $f->loadMissing(['conceptos', 'relacionada', 'pago.aplicaciones.factura', 'orden.vehiculo']);
        $tfd = $this->timbreDelXml($f);
        $qr = $f->uuid ? (new QRCode(new QROptions(['outputType' => QROutputInterface::GDIMAGE_PNG, 'scale' => 4, 'outputBase64' => true])))->render('https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?'.http_build_query([
            'id' => $f->uuid, 're' => config('tarzo.cfdi.emisor.rfc'), 'rr' => $f->receptor_rfc,
            'tt' => number_format($f->total, 6, '.', ''), 'fe' => substr((string) ($tfd['SelloCFD'] ?? ''), -8),
        ])) : null;

        return Pdf::loadView('pdf.factura', [
            'f' => $f, 'tfd' => $tfd, 'qr' => $qr, 'emisor' => config('tarzo.cfdi.emisor'),
            'simulado' => config('tarzo.cfdi.driver') === 'simulado',
        ])->setPaper('letter')->output();
    }

    /**
     * @return array<string, string>
     */
    private function timbreDelXml(Factura $f): array
    {
        if (! $f->xml_path || ! Media::disco()->exists($f->xml_path)) {
            return [];
        }

        $xml = simplexml_load_string(Media::disco()->get($f->xml_path), options: LIBXML_NONET);
        $tfd = $xml?->xpath('//*[local-name()="TimbreFiscalDigital"]')[0] ?? null;

        return $tfd ? array_map('strval', iterator_to_array($tfd->attributes())) : [];
    }
}
