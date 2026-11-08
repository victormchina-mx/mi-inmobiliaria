<?php
/**
 * Precarga del banner oscuro de las páginas interiores (images/breadcromb.webp).
 *
 * El banner es un fondo CSS escrito en el <body>, así que el navegador lo
 * descubre tarde; en celular es la imagen principal (LCP) y esperaba ~3.7 s
 * antes de descargarse. Con esta línea en <head> empieza de inmediato.
 *
 * Uso (en la vista, dentro de la sección "styles"):
 *   <?= $this->section('styles') ?>
 *   <?= view('partials/banner_preload') ?>
 *   <?= $this->endSection() ?>
 */
?>
<link rel="preload" as="image" href="<?= base_url('images/breadcromb.webp') ?>" fetchpriority="high">
