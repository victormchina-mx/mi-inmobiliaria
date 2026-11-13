<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
/**
 * Admin → Inmobiliarias → Nueva / Editar (Controlador: Admin\Agency)
 */
use App\Libraries\Agency;

$a      = $agency ?? [];
$isEdit = ! empty($a);
$val    = static fn (string $k, string $d = '') => old($k, $a[$k] ?? $d) ?? '';
$action = $isEdit ? site_url('admin/agencies/edit/' . $a['agency_id']) : site_url('admin/agencies/create');
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title"><?= $isEdit ? esc($a['name']) : 'Nueva inmobiliaria' ?></h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= site_url('admin/agencies') ?>">Inmobiliarias</a></li>
                <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nueva' ?></li>
            </ul>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <h5 class="card-title mb-3">Identidad</h5>
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="name" class="form-control" maxlength="120" required value="<?= esc($val('name'), 'attr') ?>" data-ag-name>
                </div>
                <div class="form-group">
                    <?= view('partials/file_upload', [
                        'name'     => 'logo',
                        'label'    => 'Logo (PNG, JPG o WebP; mejor con fondo transparente)',
                        'current'  => ! empty($a['logo']) ? Agency::logoUrl($a['logo']) : '',
                        'optional' => true,
                    ]) ?>
                    <?php if (! empty($a['logo'])): ?>
                    <div class="custom-control custom-checkbox mt-1"><input type="checkbox" class="custom-control-input" id="rm-logo" name="remove_logo" value="1"><label class="custom-control-label" for="rm-logo">Quitar el logo</label></div>
                    <?php elseif (($a['slug'] ?? '') === 'frices'): ?>
                    <small class="text-muted">Sin logo subido, las fichas usan el logo de Frices dibujado en el sitio.</small>
                    <?php endif ?>
                </div>
                <div class="row">
                    <div class="col-sm-6 form-group">
                        <label>Color principal (fondos)</label>
                        <div class="ag-color"><input type="color" value="<?= esc($val('color_primary', '#0A2342'), 'attr') ?>" data-ag-pick="primary"><input type="text" name="color_primary" class="form-control" maxlength="7" value="<?= esc($val('color_primary', '#0A2342'), 'attr') ?>" data-ag-hex="primary"></div>
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>Color de acento (precio, marcos)</label>
                        <div class="ag-color"><input type="color" value="<?= esc($val('color_accent', '#D1A84B'), 'attr') ?>" data-ag-pick="accent"><input type="text" name="color_accent" class="form-control" maxlength="7" value="<?= esc($val('color_accent', '#D1A84B'), 'attr') ?>" data-ag-hex="accent"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Lema <small class="text-muted">(opcional)</small></label>
                    <input type="text" name="tagline" class="form-control" maxlength="160" value="<?= esc($val('tagline'), 'attr') ?>" placeholder="Ej.: Fortaleza • Resiliencia • Integridad • Confianza">
                </div>
            </div></div>

            <div class="card"><div class="card-body">
                <h5 class="card-title mb-3">Contacto</h5>
                <div class="row">
                    <div class="col-sm-6 form-group"><label>Teléfono</label><input type="text" name="phone" class="form-control" maxlength="30" value="<?= esc($val('phone'), 'attr') ?>"></div>
                    <div class="col-sm-6 form-group"><label>WhatsApp</label><input type="text" name="whatsapp" class="form-control" maxlength="30" value="<?= esc($val('whatsapp'), 'attr') ?>"></div>
                </div>
                <div class="row">
                    <div class="col-sm-6 form-group"><label>Correo</label><input type="email" name="email" class="form-control" maxlength="150" value="<?= esc($val('email'), 'attr') ?>"></div>
                    <div class="col-sm-6 form-group"><label>Sitio web</label><input type="text" name="website" class="form-control" maxlength="200" value="<?= esc($val('website'), 'attr') ?>" placeholder="https://"></div>
                </div>
            </div></div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h5 class="card-title mb-3">Administración</h5>
                <div class="form-group">
                    <label>Encargado</label>
                    <select name="manager_uid" class="form-control" <?= $isEdit ? '' : 'disabled' ?>>
                        <option value="">Sin encargado</option>
                        <?php foreach ($candidates as $c): ?>
                        <option value="<?= (int) $c['uid'] ?>" <?= (int) old('manager_uid', (string) ($a['manager_uid'] ?? '')) === (int) $c['uid'] ? 'selected' : '' ?>><?= esc($c['uname']) ?> — <?= esc($c['uemail']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <small class="text-muted"><?= $isEdit ? 'Administra el logo, los colores y los agentes de esta inmobiliaria (sin ver a las demás). Debe ser uno de sus agentes.' : 'Primero crea la inmobiliaria y asígnale agentes; después elige al encargado.' ?></small>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="status" class="form-control">
                        <?php foreach (Agency::STATUSES as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $val('status', 'active') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= $isEdit ? 'Guardar cambios' : 'Crear inmobiliaria' ?></button>
                <a href="<?= site_url('admin/agencies') ?>" class="btn btn-secondary btn-block">Cancelar</a>
            </div></div>

            <div class="card"><div class="card-body">
                <h5 class="card-title mb-2">Vista previa</h5>
                <div class="ag-preview" data-ag-preview style="--p: <?= esc($val('color_primary', '#0A2342'), 'attr') ?>; --a: <?= esc($val('color_accent', '#D1A84B'), 'attr') ?>;">
                    <div class="ag-preview__logo">
                        <?php if (! empty($a['logo'])): ?><span class="ag-preview__plate"><img src="<?= esc(Agency::logoUrl($a['logo']), 'attr') ?>" alt=""></span>
                        <?php else: ?><span class="ag-preview__ini" data-ag-ini><?= esc(Agency::initials($val('name') ?: 'Inmobiliaria')) ?></span><?php endif ?>
                    </div>
                    <div class="ag-preview__title">Casa en <em>Venta</em></div>
                    <div class="ag-preview__price">$1,890,000</div>
                    <div class="ag-preview__band"></div>
                </div>
                <small class="text-muted">Así se verán los colores en la ficha técnica.</small>
            </div></div>
        </div>
    </div>
</form>

<style>
.ag-color { display: flex; gap: 8px; }
.ag-color input[type=color] { width: 46px; height: 38px; padding: 2px; border: 1px solid #ddd; border-radius: 6px; background: #fff; }
.ag-preview { position: relative; height: 230px; border-radius: 12px; overflow: hidden; background: var(--p); color: #fff; }
.ag-preview__logo { position: absolute; left: 16px; top: 14px; }
.ag-preview__plate { display: inline-block; padding: 6px 10px; border-radius: 8px; background: #fff; }
.ag-preview__plate img { height: 34px; width: auto; }
.ag-preview__ini { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 50%; border: 3px solid var(--a); color: var(--a); font-weight: 800; font-size: 18px; }
.ag-preview__title { position: absolute; left: 16px; top: 80px; font-size: 24px; font-weight: 800; }
.ag-preview__title em { font-style: normal; color: var(--a); }
.ag-preview__price { position: absolute; right: 14px; top: 120px; padding: 6px 12px; border-radius: 10px; background: var(--a); color: var(--p); font-weight: 800; font-size: 18px; }
.ag-preview__band { position: absolute; left: 0; bottom: 0; width: 100%; height: 60px; background: var(--a); clip-path: polygon(0 35%, 100% 0, 100% 100%, 0 100%); }
</style>
<script>
(function () {
    var prev = document.querySelector('[data-ag-preview]');
    ['primary', 'accent'].forEach(function (k) {
        var pick = document.querySelector('[data-ag-pick="' + k + '"]');
        var hex  = document.querySelector('[data-ag-hex="' + k + '"]');
        var apply = function (v) { if (/^#[0-9a-f]{6}$/i.test(v)) { prev.style.setProperty(k === 'primary' ? '--p' : '--a', v); } };
        pick.addEventListener('input', function () { hex.value = pick.value.toUpperCase(); apply(pick.value); });
        hex.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(hex.value)) { pick.value = hex.value; } apply(hex.value); });
    });
    var name = document.querySelector('[data-ag-name]'), ini = document.querySelector('[data-ag-ini]');
    if (name && ini) {
        name.addEventListener('input', function () {
            var w = name.value.replace(/^(grupo\s+)?(inmobiliari[ao]\s+)?/i, '').trim().split(/\s+/);
            ini.textContent = ((w[0] || '?').charAt(0) + (w[1] ? w[1].charAt(0) : '')).toUpperCase();
        });
    }
})();
</script>
<?= $this->endSection() ?>
