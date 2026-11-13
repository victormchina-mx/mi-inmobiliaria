<?= $this->extend('layouts/main') ?>

<?php
/**
 * Ficha técnica de una propiedad: el agente elige la plantilla y el asesor
 * que aparece, la descarga como imagen (PNG) o la imprime / guarda en PDF.
 *
 * Controlador: AgentArea::ficha · Datos: App\Libraries\FichaTecnica
 * Plantillas: app/Views/agent/ficha/{elegante,moderna,residencial}.php
 * Estilos: public/css/ficha.css · JS: public/js/ficha.js
 * Logo y colores: los de la inmobiliaria del inmueble (App\Libraries\Agency).
 */
use App\Libraries\FichaTecnica;

$f       = $ficha;
$missing = array_diff(['Terreno', 'Estacionamientos', 'Antigüedad'], array_map(static fn ($x) => in_array($x['label'], ['Estacionamiento'], true) ? 'Estacionamientos' : ($x['label'] === 'A estrenar' ? 'Antigüedad' : $x['label']), $f['facts']));
$baseUrl = site_url('agente/ficha/' . $f['pid']);
$brand   = $brand ?? \App\Libraries\Agency::brand(null);   // logo y colores de la inmobiliaria
?>

<?= $this->section('styles') ?>
<link href="https://fonts.googleapis.com/css2?family=Allura&family=Cinzel:wght@600;700&family=Cormorant+Garamond:ital,wght@1,500;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/ficha.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('lib/qrcode-generator/qrcode.js') ?>"></script>
<script src="<?= asset_url('lib/html-to-image/html-to-image.js') ?>"></script>
<script src="<?= asset_url('js/ficha.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="full-row ft-page" style="<?= esc(\App\Libraries\Agency::cssVars($brand['colors']), 'attr') ?>" data-ficha data-code="<?= esc($f['code'], 'attr') ?>" data-url="<?= esc($f['url'], 'attr') ?>"
     data-nearby="<?= site_url('property/' . $f['pid'] . '/cerca') ?>">
    <div class="container-fluid ft-wrap">
        <!-- Opciones -->
        <aside class="ft-panel">
            <a class="ft-back" href="<?= site_url('agente/propiedades') ?>"><i class="ms" aria-hidden="true">arrow_back</i>Todas las propiedades</a>
            <span class="sec-eyebrow">Ficha técnica · <?= esc($f['code']) ?></span>
            <h1 class="ft-panel__title text-capitalize"><?= esc($property['title']) ?></h1>
            <p class="ft-panel__sub"><?= esc($f['title']) ?></p>

            <h2 class="ft-label">Plantilla</h2>
            <div class="ft-templates" role="radiogroup" aria-label="Plantilla">
                <?php foreach (FichaTecnica::TEMPLATES as $key => $t): ?>
                <button type="button" class="ft-tpl<?= $key === $template ? ' is-active' : '' ?>" role="radio" aria-checked="<?= $key === $template ? 'true' : 'false' ?>" data-tpl="<?= $key ?>">
                    <span class="ft-tpl__shape ft-tpl__shape--<?= $key ?>" aria-hidden="true"></span>
                    <span><b><?= $t['name'] ?></b><small><?= $t['format'] ?></small></span>
                </button>
                <?php endforeach ?>
            </div>

            <?php if (! $isOwner): ?>
            <h2 class="ft-label">Asesor en la ficha</h2>
            <div class="ft-seg">
                <a class="<?= $useOwner ? '' : 'is-active' ?>" href="<?= $baseUrl ?>?tpl=<?= $template ?>" data-keep-tpl>Yo</a>
                <a class="<?= $useOwner ? 'is-active' : '' ?>" href="<?= $baseUrl ?>?tpl=<?= $template ?>&amp;asesor=responsable" data-keep-tpl>Responsable (<?= esc($ownerName) ?>)</a>
            </div>
            <?php endif ?>

            <h2 class="ft-label">Descargar y compartir</h2>
            <div class="ft-actions">
                <button type="button" class="ft-btn ft-btn--gold" data-download><i class="ms" aria-hidden="true">download</i>Descargar imagen (PNG)</button>
                <button type="button" class="ft-btn ft-btn--dark" data-print><i class="ms" aria-hidden="true">print</i>Imprimir o guardar PDF</button>
                <a class="ft-btn ft-btn--wa" href="https://wa.me/?text=<?= rawurlencode($f['title'] . ' · ' . $f['price'] . ($f['isRent'] ? ' al mes' : '') . ' · ' . $f['url']) ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89A11.82 11.82 0 0 0 12.05 0zm5.42 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.27-.2-.57-.35z"/></svg>
                    Enviar enlace por WhatsApp
                </a>
                <button type="button" class="ft-btn ft-btn--ghost" data-copy="<?= esc($f['url'], 'attr') ?>"><i class="ms" aria-hidden="true">content_copy</i>Copiar enlace <?= esc($f['shortUrl']) ?></button>
            </div>
            <p class="ft-status" data-status aria-live="polite"></p>

            <?php if ($missing): ?>
            <div class="ft-tip">
                <i class="ms" aria-hidden="true">info</i>
                <div>
                    <b>Faltan datos en la ficha:</b> <?= esc(implode(', ', $missing)) ?>.
                    <?php if ($isOwner): ?>
                    <a href="<?= site_url('my-properties/edit/' . $f['pid']) ?>">Complétalos en Editar propiedad</a>.
                    <?php else: ?>
                    Pídele a <?= esc($ownerName) ?> que los complete.
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>
        </aside>

        <!-- Vista previa -->
        <div class="ft-preview" data-preview>
            <?php foreach (array_keys(FichaTecnica::TEMPLATES) as $key): ?>
            <div class="ft-stage<?= $key === $template ? ' is-active' : '' ?>" data-stage="<?= $key ?>">
                <?= view('agent/ficha/' . $key, ['f' => $f, 'brand' => $brand]) ?>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
