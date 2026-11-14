<?= $this->extend('layouts/main') ?>

<?php
/**
 * "Mi inmobiliaria" (solo la encargada): pestañas Identidad y Agentes.
 * Controlador: AgencyPanel · Estilos: .ma-* en style.css
 */
use App\Libraries\Agency;
use App\Libraries\FichaTecnica;

$a       = $agency;
$val     = static fn (string $k, string $d = '') => old($k, $a[$k] ?? $d) ?? '';
$primary = $val('color_primary', '#0A2342');
$accent  = $val('color_accent', '#D1A84B');
$brand   = Agency::brand($a);
$swatchP = ['#0A2342', '#0F3D2E', '#7A1F2B', '#1F2937', '#2B2D42'];
$swatchA = ['#D1A84B', '#E8C27A', '#C0C7D1', '#E07A5F', '#F2E8DC'];
?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Cormorant+Garamond:ital,wght@1,600&display=swap" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/logo-cropper.js') ?>"></script>
<script src="<?= asset_url('js/agency-panel.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-8"><h1 class="h2 page-name float-left text-white text-uppercase">Mi inmobiliaria</h1></div>
        </div>
    </div>
</div>

<div class="full-row ap-page">
    <div class="container">
        <div class="ap-head">
            <div>
                <span class="sec-eyebrow">Encargado · <?= esc($a['name']) ?></span>
                <h2 class="sec-title"><?= $tab === 'agentes' ? 'Agentes de ' . esc(Agency::shortName($a['name'])) : 'Mi inmobiliaria' ?></h2>
                <p class="ap-sub"><?= $tab === 'agentes'
                    ? 'Agrega agentes con el correo de su cuenta del sitio. Al quitarlos quedan como independientes, con sus propiedades.'
                    : 'Solo ves y administras tu inmobiliaria: identidad y agentes.' ?></p>
            </div>
            <nav class="ap-tabs" aria-label="Mi inmobiliaria">
                <a class="ap-tab<?= $tab === 'identidad' ? ' is-active' : '' ?>" href="<?= site_url('agente/inmobiliaria') ?>"<?= $tab === 'identidad' ? ' aria-current="page"' : '' ?>><i class="ms" aria-hidden="true">palette</i>Identidad</a>
                <a class="ap-tab<?= $tab === 'agentes' ? ' is-active' : '' ?>" href="<?= site_url('agente/inmobiliaria/agentes') ?>"<?= $tab === 'agentes' ? ' aria-current="page"' : '' ?>><i class="ms" aria-hidden="true">badge</i>Agentes <small class="ma-count"><?= count($agents) ?></small></a>
            </nav>
        </div>

<?php if ($tab === 'identidad'): ?>
        <form method="post" action="<?= site_url('agente/inmobiliaria') ?>" enctype="multipart/form-data" class="ma-grid">
            <?= csrf_field() ?>
            <section class="ma-card">
                <h3 class="ma-card__title">Logo y colores</h3>
                <?= view('partials/file_upload', [
                    'name'     => 'logo',
                    'label'    => 'Logo (PNG, JPG o WebP; mejor con fondo transparente)',
                    'current'  => ! empty($a['logo']) ? Agency::logoUrl($a['logo']) : '',
                    'optional' => true,
                    'labelClass' => 'ma-label',
                ]) ?>
                <?= view('partials/logo_cropper', [
                    'input' => 'fu-logo',
                    'src'   => ! empty($a['logo']) ? Agency::logoUrl($a['logo']) : '',
                    'name'  => Agency::shortName($a['name']),
                ]) ?>
                <?php if (! empty($a['logo'])): ?>
                <label class="ma-check"><input type="checkbox" name="remove_logo" value="1"> Quitar el logo</label>
                <?php elseif ($a['slug'] === 'frices'): ?>
                <p class="ma-help">Sin logo subido, las fichas usan el logo de Frices dibujado en el sitio.</p>
                <?php endif ?>

                <div class="ma-field">
                    <span>Color principal (fondos)</span>
                    <div class="ma-colors">
                        <?php foreach ($swatchP as $c): ?><button type="button" class="ma-sw<?= strcasecmp($c, $primary) === 0 ? ' is-on' : '' ?>" style="background: <?= $c ?>" data-ma-sw="primary" data-color="<?= $c ?>" aria-label="Color <?= $c ?>"></button><?php endforeach ?>
                        <input type="color" value="<?= esc($primary, 'attr') ?>" data-ma-pick="primary" aria-label="Otro color principal">
                        <input type="text" name="color_primary" value="<?= esc($primary, 'attr') ?>" maxlength="7" data-ma-hex="primary" class="ma-hex">
                    </div>
                </div>
                <div class="ma-field">
                    <span>Color de acento (precio, marcos)</span>
                    <div class="ma-colors">
                        <?php foreach ($swatchA as $c): ?><button type="button" class="ma-sw<?= strcasecmp($c, $accent) === 0 ? ' is-on' : '' ?>" style="background: <?= $c ?>" data-ma-sw="accent" data-color="<?= $c ?>" aria-label="Color <?= $c ?>"></button><?php endforeach ?>
                        <input type="color" value="<?= esc($accent, 'attr') ?>" data-ma-pick="accent" aria-label="Otro color de acento">
                        <input type="text" name="color_accent" value="<?= esc($accent, 'attr') ?>" maxlength="7" data-ma-hex="accent" class="ma-hex">
                    </div>
                </div>
                <label class="ma-field"><span>Lema <small>(opcional)</small></span><input type="text" name="tagline" maxlength="160" value="<?= esc($val('tagline'), 'attr') ?>" placeholder="Ej.: Fortaleza • Resiliencia • Integridad • Confianza"></label>
            </section>

            <section class="ma-card">
                <h3 class="ma-card__title">Datos de contacto</h3>
                <label class="ma-field"><span>Nombre comercial</span><input type="text" name="name" maxlength="120" required value="<?= esc($val('name'), 'attr') ?>"></label>
                <div class="ma-row">
                    <label class="ma-field"><span>Teléfono</span><input type="text" name="phone" maxlength="30" value="<?= esc($val('phone'), 'attr') ?>"></label>
                    <label class="ma-field"><span>WhatsApp</span><input type="text" name="whatsapp" maxlength="30" value="<?= esc($val('whatsapp'), 'attr') ?>"></label>
                </div>
                <label class="ma-field"><span>Correo</span><input type="email" name="email" maxlength="150" value="<?= esc($val('email'), 'attr') ?>"></label>
                <label class="ma-field"><span>Sitio web</span><input type="text" name="website" maxlength="200" value="<?= esc($val('website'), 'attr') ?>" placeholder="https://"></label>
                <p class="ma-note"><i class="ms" aria-hidden="true">info</i>El nombre y el logo aparecen en el portal junto a cada propiedad de tus agentes y en sus fichas técnicas.</p>
                <button type="submit" class="ma-save"><i class="ms" aria-hidden="true">save</i>Guardar cambios</button>
            </section>

            <aside class="ma-preview">
                <div class="ma-preview__label">Vista previa de la ficha</div>
                <div class="ma-ficha" data-ma-preview style="--p: <?= esc($primary, 'attr') ?>; --a: <?= esc($accent, 'attr') ?>;">
                    <div class="ma-ficha__photo"></div>
                    <div class="ma-ficha__band"></div>
                    <div class="ma-ficha__logo">
                        <?php if ($brand['frices']): ?>
                        <div class="ma-fx">
                            <svg viewBox="0 0 100 100" aria-hidden="true"><path d="M66.5 25 L24 50 H33.5 V72 H55 V58 H66 V67 Q66 71.5 70.5 70.5 C80 68 90 59 91.8 46.3 A42 42 0 0 0 50 8 A42 42 0 0 0 8 50 A42 42 0 0 0 50 92 A42 42 0 0 0 89.3 64.7" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <b>GRUPO<br>INMOBILIARIO<br>FRICES</b>
                        </div>
                        <?php elseif (! empty($a['logo'])): ?>
                        <span class="ma-ficha__plate"><img src="<?= esc(Agency::logoUrl($a['logo']), 'attr') ?>" alt=""></span>
                        <?php else: ?>
                        <span class="ma-ficha__ini"><?= esc(Agency::initials($a['name'])) ?></span>
                        <?php endif ?>
                    </div>
                    <div class="ma-ficha__title">Casa en <em>Venta</em><span>Hacienda del Valle</span></div>
                    <div class="ma-ficha__price">$1,890,000</div>
                    <div class="ma-ficha__agent"><?= esc(session()->get('uname') ?? '') ?><small>Asesor inmobiliario</small></div>
                </div>
                <p class="ma-help">Cambia en vivo al elegir colores. Guarda para aplicarlo en el portal y en las fichas.</p>
            </aside>
        </form>

<?php else: ?>
        <form method="post" action="<?= site_url('agente/inmobiliaria/agentes/agregar') ?>" class="ma-add">
            <?= csrf_field() ?>
            <i class="ms" aria-hidden="true">person_add</i>
            <input type="email" name="email" required maxlength="150" value="<?= esc(old('email') ?? '', 'attr') ?>" placeholder="Correo del agente (ej. agente@correo.com)" aria-label="Correo del agente">
            <button type="submit" class="ap-btn ap-btn--dark"><i class="ms" aria-hidden="true">add</i>Agregar a <?= esc(Agency::shortName($a['name'])) ?></button>
        </form>

        <div class="ma-card ma-card--table">
            <table class="ma-table">
                <thead><tr><th>Agente</th><th>Correo</th><th class="text-center">Propiedades</th><th class="text-center">Citas (mes)</th><th>Rol</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($agents as $g): $isManager = (int) $a['manager_uid'] === (int) $g['uid']; ?>
                    <tr>
                        <td>
                            <div class="ma-agent">
                                <?php if (! empty($g['uimage'])): ?>
                                <img src="<?= esc(thumb_url('user', $g['uimage'], 200)) ?>" alt="" width="38" height="38" loading="lazy">
                                <?php else: ?>
                                <span class="ma-agent__ini"><?= esc(mb_strtoupper(mb_substr((string) $g['uname'], 0, 1))) ?></span>
                                <?php endif ?>
                                <b><?= esc($g['uname']) ?></b>
                            </div>
                        </td>
                        <td class="ma-mail"><?= esc($g['uemail']) ?></td>
                        <td class="text-center"><?= (int) ($props[$g['uid']] ?? 0) ?></td>
                        <td class="text-center"><?= (int) ($citas[$g['uid']] ?? 0) ?></td>
                        <td><?php if ($isManager): ?><span class="ma-role">★ Encargado</span><?php else: ?><?= $g['utype'] === 'builder' ? 'Constructor' : 'Agente' ?><?php endif ?></td>
                        <td class="text-right">
                            <?php if (! $isManager): ?>
                            <form method="post" action="<?= site_url('agente/inmobiliaria/agentes/' . (int) $g['uid'] . '/quitar') ?>" data-confirm="¿Quitar a <?= esc($g['uname'], 'attr') ?> de <?= esc($a['name'], 'attr') ?>? Quedará como independiente con sus propiedades.">
                                <?= csrf_field() ?>
                                <button type="submit" class="ma-remove">Quitar</button>
                            </form>
                            <?php else: ?>—<?php endif ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="ma-note"><i class="ms" aria-hidden="true">lock</i>Solo el administrador puede nombrar encargados, mover agentes entre inmobiliarias y crear inmobiliarias. No ves a las demás inmobiliarias.</p>
<?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
