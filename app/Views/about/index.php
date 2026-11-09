<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h1 class="h2 page-name float-left text-white text-uppercase">Nosotros</h1></div>
        </div>
    </div>
</div>

<?php
// Datos de contacto del sitio (los mismos de la barra superior y el pie)
$sitePhone = '55 7606 2384';
$siteEmail = 'inmobiliaria.info@vmch.net';
$siteMsg   = 'Hola, quiero información sobre sus propiedades.';
$waHref    = 'https://wa.me/' . \App\Libraries\ContactLinks::whatsappNumber($sitePhone) . '?text=' . rawurlencode($siteMsg);
$mailHref  = 'mailto:' . $siteEmail . '?subject=' . rawurlencode('Información sobre propiedades') . '&body=' . rawurlencode($siteMsg);
?>
<div class="full-row">
    <div class="container">
        <?php if ($about): ?>
        <?php // Título, imagen y texto en rejilla: en celular la imagen va debajo del título ?>
        <div class="about-grid<?= empty($about['image']) ? ' about-grid--no-image' : '' ?>">
            <div class="about-grid__title">
                <span class="sec-eyebrow">Nosotros</span>
                <h2 class="sec-title about-title"><?= esc($about['title']) ?></h2>
            </div>
            <?php if (! empty($about['image'])): ?>
            <div class="about-grid__image">
                <img src="<?= esc(thumb_url('about', $about['image'], 800)) ?>" loading="lazy" decoding="async" alt="<?= esc($about['title']) ?>">
            </div>
            <?php endif ?>
            <div class="about-grid__content about-content">
                <?= \App\Libraries\HtmlSanitizer::clean($about['content']) ?>
            </div>
        </div>

        <!-- Contacto -->
        <div class="about-contact">
            <div class="about-contact__text">
                <span class="sec-eyebrow">¿Hablamos?</span>
                <h3 class="about-contact__title">¿Buscas una propiedad o quieres vender o rentar la tuya?</h3>
                <p>Llámanos al <strong><?= esc($sitePhone) ?></strong> o escríbenos a <strong><?= esc($siteEmail) ?></strong>. Con gusto te atendemos.</p>
            </div>
            <div class="about-contact__actions">
                <a class="ac-btn ac-btn--call" href="<?= esc(\App\Libraries\ContactLinks::tel($sitePhone), 'attr') ?>"><i class="ms" aria-hidden="true">call</i>Llamar</a>
                <a class="ac-btn ac-btn--wa" href="<?= esc($waHref, 'attr') ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.27-.2-.57-.35m-5.42 7.4h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41z"/></svg>WhatsApp
                </a>
                <a class="ac-btn ac-btn--mail" href="<?= esc($mailHref, 'attr') ?>"><i class="ms" aria-hidden="true">mail</i>Correo</a>
            </div>
        </div>
        <?php else: ?>
        <p class="text-center text-muted py-5">Información no disponible.</p>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
