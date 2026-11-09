<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h1 class="h2 page-name float-left text-white text-uppercase">Contacto</h1></div>
        </div>
    </div>
</div>

<?php $site = config('Site'); ?>
<div class="full-row">
    <div class="container">
        <div class="contact-grid">
            <!-- Datos de contacto -->
            <aside class="contact-info">
                <span class="sec-eyebrow">Contacto</span>
                <h2 class="contact-info__title">Hablemos</h2>
                <p class="contact-info__text">¿Buscas una propiedad o quieres vender o rentar la tuya? Escríbenos o llámanos; con gusto te atendemos.</p>

                <ul class="contact-info__list">
                    <li>
                        <span class="contact-info__ic" aria-hidden="true"><i class="ms">call</i></span>
                        <div><small>Teléfono y WhatsApp</small><a href="<?= esc(\App\Libraries\ContactLinks::tel($site->phone), 'attr') ?>"><?= esc($site->phone) ?></a></div>
                    </li>
                    <li>
                        <span class="contact-info__ic" aria-hidden="true"><i class="ms">mail</i></span>
                        <div><small>Correo</small><a href="mailto:<?= esc($site->email, 'attr') ?>"><?= esc($site->email) ?></a></div>
                    </li>
                    <li>
                        <span class="contact-info__ic" aria-hidden="true"><i class="ms">location_on</i></span>
                        <div><small>Zonas</small><span>Ciudad de México, Estado de México y Aguascalientes</span></div>
                    </li>
                </ul>

                <div class="contact-info__actions">
                    <?= view('partials/site_contact_buttons') ?>
                </div>
            </aside>

            <!-- Formulario -->
            <div class="contact-form">
                <h3 class="contact-form__title">Envíanos un mensaje</h3>
                <p class="contact-form__text">Te respondemos por correo o por teléfono, lo antes posible.</p>
                <form method="post" action="<?= site_url('contact') ?>">
                    <?= csrf_field() ?>
                    <div class="contact-form__row">
                        <div class="cf-field">
                            <label for="cf-name">Nombre <span aria-hidden="true">*</span></label>
                            <input type="text" id="cf-name" name="name" class="form-control" autocomplete="name" maxlength="100"
                                   value="<?= esc(old('name')) ?>" required>
                        </div>
                        <div class="cf-field">
                            <label for="cf-email">Correo electrónico <span aria-hidden="true">*</span></label>
                            <input type="email" id="cf-email" name="email" class="form-control" autocomplete="email"
                                   value="<?= esc(old('email')) ?>" required>
                        </div>
                    </div>
                    <div class="contact-form__row">
                        <div class="cf-field">
                            <label for="cf-phone">Teléfono <span class="cf-optional">(opcional)</span></label>
                            <input type="tel" id="cf-phone" name="phone" class="form-control" autocomplete="tel" maxlength="20"
                                   value="<?= esc(old('phone')) ?>">
                        </div>
                        <div class="cf-field">
                            <label for="cf-subject">Asunto <span aria-hidden="true">*</span></label>
                            <input type="text" id="cf-subject" name="subject" class="form-control" maxlength="100"
                                   placeholder="Ej.: Quiero vender mi casa" value="<?= esc(old('subject')) ?>" required>
                        </div>
                    </div>
                    <div class="cf-field">
                        <label for="cf-message">Mensaje <span aria-hidden="true">*</span></label>
                        <textarea id="cf-message" name="message" class="form-control" rows="5"
                                  placeholder="Cuéntanos qué buscas: zona, tipo de propiedad, presupuesto…" required><?= esc(old('message')) ?></textarea>
                    </div>
                    <button type="submit" class="cf-submit">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Enviar mensaje
                    </button>
                    <p class="cf-note">Los campos con * son obligatorios. Usamos tus datos solo para responderte.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
