<?php

namespace App\Libraries;

/**
 * Foto de fondo del banner de las páginas interiores (Nosotros, Contacto,
 * Propiedades, Agentes, Mi cuenta, área de agentes…).
 * Se cambia en Admin → Fondos → Banner de páginas. Lógica: Backdrop.
 *
 * En las vistas:
 *   <div class="banner-full-row page-banner" <?= \App\Libraries\PageBanner::style() ?>>
 */
class PageBanner extends Backdrop
{
    public const DEFAULT    = 'images/breadcromb.webp';
    protected const DIR     = 'banner';
    protected const CONFIG  = 'page-banner.json';
    protected const PREFIX  = 'banner';

    /** Atributo style del banner: foto, encuadre e intensidad del degradado. */
    public static function style(): string
    {
        return 'style="' . esc(
            "background-image:url('" . static::url() . "'); background-position:" . static::css() . '; --banner-shade:' . static::shadeCss() . ';',
            'attr'
        ) . '"';
    }
}
