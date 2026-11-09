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

<?php $site = config('Site'); ?>
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
                <p>Llámanos al <strong><?= esc($site->phone) ?></strong> o escríbenos a <strong><?= esc($site->email) ?></strong>. Con gusto te atendemos.</p>
            </div>
            <div class="about-contact__actions">
                <?= view('partials/site_contact_buttons') ?>
            </div>
        </div>
        <?php else: ?>
        <p class="text-center text-muted py-5">Información no disponible.</p>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
