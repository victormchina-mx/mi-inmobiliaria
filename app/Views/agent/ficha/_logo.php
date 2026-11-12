<?php
/**
 * Logo de Grupo Inmobiliario Frices para las fichas técnicas: emblema en
 * SVG (aro dorado con la casa) y el nombre en texto, para que se vea nítido
 * en el PNG y al imprimir. Colores: --fx-* en public/css/ficha.css.
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
        <circle cx="50" cy="50" r="44" fill="none" stroke="url(#<?= $g ?>)" stroke-width="7"/>
        <g fill="none" stroke="url(#<?= $g ?>)" stroke-width="4.2" stroke-linejoin="miter" stroke-linecap="square">
            <path d="M25 50 L50 27 L75 50"/>
            <path d="M65 37 V29 H70 V42"/>
            <path d="M32 44 V71 H68 V44"/>
        </g>
        <g fill="url(#<?= $g ?>)">
            <rect x="42" y="49" width="7" height="7" rx=".8"/><rect x="51" y="49" width="7" height="7" rx=".8"/>
            <rect x="42" y="58" width="7" height="7" rx=".8"/><rect x="51" y="58" width="7" height="7" rx=".8"/>
        </g>
    </svg>
    <div class="fx-logo__text">
        <b>GRUPO<br>INMOBILIARIO<br><span>FRICES</span></b>
        <small>Fortaleza · Resiliencia · Integridad · Confianza</small>
    </div>
</div>
