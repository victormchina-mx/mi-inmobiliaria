<?php
/**
 * Logo de Grupo Inmobiliario Frices para las fichas técnicas: emblema en
 * SVG (aro abierto que forma la casa en un solo trazo) y el nombre en
 * texto, para que se vea nítido en el PNG y al imprimir. Colores: --fx-* en public/css/ficha.css.
 *
 * Variables: $id (único por plantilla, para el degradado del SVG)
 */
$g = 'fxg-' . ($id ?? 'logo');
?>
<div class="fx-logo">
    <svg class="fx-logo__mark" viewBox="0 0 100 100" aria-hidden="true">
        <defs>
            <linearGradient id="<?= $g ?>" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#F3D98A"/>
                <stop offset=".5" stop-color="#D1A84B"/>
                <stop offset="1" stop-color="#A47A27"/>
            </linearGradient>
        </defs>
        <path d="M66.5 25 L24 50 H33.5 V72 H55 V58 H66 V67 Q66 71.5 70.5 70.5 C80 68 90 59 91.8 46.3 A42 42 0 0 0 50 8 A42 42 0 0 0 8 50 A42 42 0 0 0 50 92 A42 42 0 0 0 89.3 64.7"
              fill="none" stroke="url(#<?= $g ?>)" stroke-width="4.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <div class="fx-logo__text">
        <b>GRUPO<br>INMOBILIARIO<br><span>FRICES</span></b>
        <small>Fortaleza · Resiliencia · Integridad · Confianza</small>
    </div>
</div>
