<?= $this->extend('layouts/admin') ?>
<?php
/**
 * Admin → Portada del inicio (Controlador: Admin\HomeHero · Reglas: App\Libraries\HomeHero)
 * La vista previa imita la portada real: foto, degradado oscuro y el título.
 */
use App\Libraries\HomeHero;
?>

<?= $this->section('styles') ?>
<style>
.hh-preview { position: relative; overflow: hidden; aspect-ratio: 16 / 8; min-height: 240px; border-radius: 14px; background: #0F172A center / cover no-repeat; box-shadow: 0 18px 40px -26px rgba(15, 23, 42, .7); transition: background-position .3s; }
.hh-preview::before { content: ''; position: absolute; inset: 0; opacity: var(--shade, 1); transition: opacity .15s; background: linear-gradient(135deg, rgba(15, 23, 42, .84) 0%, rgba(15, 23, 42, .58) 55%, rgba(201, 169, 110, .10) 100%); }
.hh-preview__text { position: absolute; left: 6%; top: 50%; transform: translateY(-50%); color: #fff; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(22px, 3.4vw, 42px); font-weight: 700; line-height: 1.1; }
.hh-preview__text span { color: #C9A96E; }
.hh-preview__bar { position: absolute; left: 6%; right: 30%; bottom: 14%; height: 12%; max-height: 46px; border-radius: 8px; background: rgba(255, 255, 255, .9); }
.hh-tag { position: absolute; top: 12px; right: 12px; z-index: 1; padding: 3px 10px; border-radius: 20px; background: rgba(255, 255, 255, .92); color: #0F172A; font-size: 12px; font-weight: 700; }
.hh-pos { display: flex; gap: 8px; flex-wrap: wrap; }
.hh-pos label { flex: 1; min-width: 90px; margin: 0; cursor: pointer; }
.hh-pos input { position: absolute; opacity: 0; }
.hh-pos span { display: block; padding: 9px 10px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center; font-weight: 600; }
.hh-pos input:checked + span { border-color: #C9A96E; background: #FBF7EF; color: #8A5A2B; box-shadow: inset 0 0 0 1px #C9A96E; }
.hh-drop { display: block; padding: 22px; border: 2px dashed #d5d9e0; border-radius: 12px; text-align: center; color: #6B7280; cursor: pointer; transition: border-color .2s, background .2s; }
.hh-drop:hover, .hh-drop.is-over { border-color: #C9A96E; background: #FFFCF6; }
.hh-drop b { display: block; color: #0F172A; }
.hh-drop input { display: none; }
.hh-shade { display: flex; align-items: center; gap: 12px; }
.hh-shade input[type=range] { flex: 1; accent-color: #B07D4F; }
.hh-shade output { min-width: 52px; padding: 4px 8px; border-radius: 6px; background: #F3F1EC; text-align: center; font-weight: 700; color: #0F172A; }
.hh-presets { display: flex; gap: 6px; margin-top: 8px; }
.hh-presets button { flex: 1; padding: 6px 4px; border-radius: 8px; border: 1px solid #dee2e6; background: #fff; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; }
.hh-presets button:hover, .hh-presets button.is-on { border-color: #C9A96E; background: #FBF7EF; color: #8A5A2B; }
.hh-warn { margin-top: 8px; font-size: 12.5px; color: #B45309; }
.hh-file { margin-top: 8px; font-size: 13px; color: #2E7D4F; font-weight: 600; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">Portada del inicio</h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= site_url('admin/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Portada del inicio</li>
            </ul>
        </div>
        <div class="col-auto">
            <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm"><i class="fe fe-eye"></i> Ver el inicio</a>
        </div>
    </div>
</div>


<form method="post" action="<?= site_url('admin/portada') ?>" enctype="multipart/form-data" id="hh-form">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <h5 class="card-title mb-3">Vista previa</h5>
                <div class="hh-preview" id="hh-preview" style="background-image:url('<?= esc($url, 'attr') ?>'); background-position: <?= esc(HomeHero::POSITIONS[$position][1], 'attr') ?>; --shade: <?= $shade / 100 ?>;">
                    <span class="hh-tag" id="hh-tag"><?= $custom ? 'Foto actual' : 'Foto original' ?></span>
                    <div class="hh-preview__text"><span>Déjanos</span><br>Guiarte a tu Hogar</div>
                    <div class="hh-preview__bar"></div>
                </div>
                <p class="text-muted small mt-2 mb-0">Así se verá en el inicio. El degradado oscuro hace legible el texto; ajústalo a la derecha. En celular se muestra la parte central de la foto.</p>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h5 class="card-title mb-3">Cambiar la foto</h5>
                <label class="hh-drop" id="hh-drop">
                    <i class="fe fe-upload" style="font-size:26px"></i>
                    <b>Elige o arrastra una foto</b>
                    JPG, PNG o WebP · hasta 15 MB
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" id="hh-input">
                    <div class="hh-file" id="hh-file" hidden></div>
                </label>
                <ul class="small text-muted mt-3 pl-3 mb-0">
                    <li>Horizontal, de preferencia <b>1920×1080 px</b> o más.</li>
                    <li>Mejor si el lado izquierdo es sencillo: ahí va el texto.</li>
                    <li>Se optimiza sola para que la página cargue rápido.</li>
                </ul>

                <h5 class="card-title mt-4 mb-2">Encuadre</h5>
                <p class="small text-muted mb-2">Qué parte de la foto se ve si no cabe completa.</p>
                <div class="hh-pos">
                    <?php foreach (HomeHero::POSITIONS as $key => [$label, $css]): ?>
                    <label><input type="radio" name="position" value="<?= $key ?>" data-css="<?= esc($css, 'attr') ?>"<?= $position === $key ? ' checked' : '' ?>><span><?= $label ?></span></label>
                    <?php endforeach ?>
                </div>

                <h5 class="card-title mt-4 mb-2">Degradado oscuro</h5>
                <p class="small text-muted mb-2">Más oscuro: el texto se lee mejor. Más claro: la foto luce más.</p>
                <div class="hh-shade">
                    <input type="range" name="shade" id="hh-shade" min="<?= HomeHero::SHADE_MIN ?>" max="<?= HomeHero::SHADE_MAX ?>" step="5" value="<?= (int) $shade ?>" aria-label="Intensidad del degradado oscuro">
                    <output id="hh-shade-out" for="hh-shade"><?= (int) $shade ?>%</output>
                </div>
                <div class="hh-presets">
                    <button type="button" data-shade="40">Suave</button>
                    <button type="button" data-shade="70">Medio</button>
                    <button type="button" data-shade="100">Original</button>
                </div>
                <p class="hh-warn" id="hh-warn"<?= $shade < 40 ? '' : ' hidden' ?>>Con tan poco degradado el título puede leerse mal sobre fotos claras.</p>

                <button type="submit" class="btn btn-primary btn-block mt-4"><i class="fe fe-check"></i> Guardar</button>
            </div></div>

            <?php if ($custom || $shade !== HomeHero::SHADE_DEFAULT || $position !== 'center'): ?>
            <div class="card"><div class="card-body">
                <h6 class="mb-2">¿Volver a la portada original?</h6>
                <p class="small text-muted">Se borra la foto subida y regresa la de la plantilla, con el encuadre y el degradado originales.</p>
                <button type="submit" form="hh-reset" class="btn btn-outline-danger btn-sm"><i class="fe fe-loop"></i> Restaurar la original</button>
            </div></div>
            <?php endif ?>
        </div>
    </div>
</form>

<?php if ($custom || $shade !== HomeHero::SHADE_DEFAULT || $position !== 'center'): ?>
<form method="post" action="<?= site_url('admin/portada/restaurar') ?>" id="hh-reset" onsubmit="return confirm('¿Restaurar la portada original?');">
    <?= csrf_field() ?>
</form>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var preview = document.getElementById('hh-preview');
    var input   = document.getElementById('hh-input');
    var drop    = document.getElementById('hh-drop');
    var info    = document.getElementById('hh-file');
    var tag     = document.getElementById('hh-tag');

    function show(file) {
        if (!file || !/^image\//.test(file.type)) { return; }
        var url = URL.createObjectURL(file);
        preview.style.backgroundImage = 'url("' + url + '")';
        tag.textContent = 'Foto nueva (sin guardar)';
        var img = new Image();
        img.onload = function () {
            var small = img.naturalWidth < 1200;
            info.hidden = false;
            info.style.color = small ? '#B91C1C' : '#2E7D4F';
            info.textContent = file.name + ' · ' + img.naturalWidth + '×' + img.naturalHeight + ' px' + (small ? ' — muy pequeña, usa una de 1200 px o más' : '');
        };
        img.src = url;
    }
    input.addEventListener('change', function () { show(input.files[0]); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            show(input.files[0]);
        }
    });

    // Degradado oscuro
    var shade = document.getElementById('hh-shade');
    var out   = document.getElementById('hh-shade-out');
    var warn  = document.getElementById('hh-warn');
    var presets = document.querySelectorAll('.hh-presets button');
    function setShade(v) {
        v = parseInt(v, 10);
        shade.value = v;
        out.textContent = v + '%';
        preview.style.setProperty('--shade', v / 100);
        warn.hidden = v >= 40;
        presets.forEach(function (b) { b.classList.toggle('is-on', parseInt(b.getAttribute('data-shade'), 10) === v); });
    }
    shade.addEventListener('input', function () { setShade(shade.value); });
    presets.forEach(function (b) {
        b.addEventListener('click', function () { setShade(b.getAttribute('data-shade')); });
    });
    setShade(shade.value);

    document.querySelectorAll('.hh-pos input').forEach(function (r) {
        r.addEventListener('change', function () { preview.style.backgroundPosition = r.getAttribute('data-css'); });
    });
})();
</script>
<?= $this->endSection() ?>
