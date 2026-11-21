<?php

namespace App\Support;

use App\Enums\ZonaVehiculo;

/**
 * SVG de vista superior del vehículo con las zonas dañadas resaltadas.
 * Se usa en el panel, en la página de firma y en el PDF (como imagen).
 */
final class DiagramaDanos
{
    /**
     * @param  iterable<ZonaVehiculo|string>  $zonasMarcadas
     */
    public static function svg(iterable $zonasMarcadas, int $ancho = 200): string
    {
        $marcadas = collect($zonasMarcadas)
            ->map(fn ($z) => $z instanceof ZonaVehiculo ? $z : ZonaVehiculo::tryFrom((string) $z))
            ->filter()
            ->countBy(fn (ZonaVehiculo $z) => $z->value);

        $alto = (int) round($ancho * 2);
        $partes = [];

        // Silueta
        $partes[] = '<rect x="10" y="4" width="180" height="390" rx="46" fill="#f3f5f6" stroke="#7a868c" stroke-width="2"/>';

        foreach (ZonaVehiculo::cases() as $zona) {
            $caja = $zona->diagrama();
            if ($caja === null) {
                continue;
            }
            [$x, $y, $w, $h] = $caja;
            $n = $marcadas->get($zona->value, 0);
            $relleno = $n > 0 ? '#e5484d' : '#ffffff';
            $opacidad = $n > 0 ? '0.85' : '1';
            $titulo = htmlspecialchars($zona->getLabel(), ENT_XML1);
            $partes[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" rx=\"4\" fill=\"{$relleno}\" fill-opacity=\"{$opacidad}\" stroke=\"#7a868c\" stroke-width=\"1\"><title>{$titulo}</title></rect>";
            if ($n > 1) {
                $cx = $x + $w / 2;
                $cy = $y + $h / 2 + 4;
                $partes[] = "<text x=\"{$cx}\" y=\"{$cy}\" text-anchor=\"middle\" font-size=\"12\" font-family=\"sans-serif\" font-weight=\"bold\" fill=\"#ffffff\">{$n}</text>";
            }
        }

        $partes[] = '<text x="100" y="22" text-anchor="middle" font-size="9" font-family="sans-serif" fill="#5a666c">FRENTE</text>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 400" width="'.$ancho.'" height="'.$alto.'" role="img" aria-label="Diagrama de daños previos">'
            .implode('', $partes).'</svg>';
    }

    public static function dataUri(iterable $zonasMarcadas, int $ancho = 200): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($zonasMarcadas, $ancho));
    }
}
