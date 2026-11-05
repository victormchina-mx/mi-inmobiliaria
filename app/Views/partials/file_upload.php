<?php
/**
 * Campo de imagen con vista previa.
 *
 * Uso:
 *   <?= view('partials/file_upload', [
 *       'name'     => 'aimage',                 // name del <input type="file"> (obligatorio)
 *       'label'    => 'Imagen principal',       // texto del label
 *       'current'  => base_url('uploads/...'),  // URL de la imagen guardada (opcional)
 *       'tile'     => true,                     // variante mosaico para columnas angostas
 *       'avatar'   => false,                    // ícono de usuario en lugar de imagen
 *       'optional' => true,                     // agrega "(opcional)" al label
 *       'labelClass' => 'text-secondary',       // clases extra del label
 *   ]) ?>
 *
 * Estilos: public/css/file-upload.css · Comportamiento: public/js/file-upload.js
 */
$name     = $name ?? 'image';
$id       = $id ?? ('fu-' . preg_replace('/[^a-z0-9_-]/i', '-', $name));
$label    = $label ?? 'Imagen';
$current  = $current ?? '';
$tile     = ! empty($tile);
$avatar   = ! empty($avatar);
$optional = ! empty($optional);
$labelClass = trim('d-block ' . ($labelClass ?? ''));

$chooseText = $avatar ? 'Elegir foto' : 'Elegir imagen';
$changeText = $avatar ? 'Cambiar foto' : 'Cambiar imagen';
$btnText    = $current ? $changeText : $chooseText;
$nameText   = $current ? 'Imagen actual' : 'Ningún archivo seleccionado';
?>
<label class="<?= esc($labelClass, 'attr') ?>" for="<?= esc($id, 'attr') ?>"><?= esc($label) ?><?php if ($optional): ?> <span class="file-upload__optional">(opcional)</span><?php endif ?></label>
<div class="file-upload<?= $tile ? ' file-upload--tile' : '' ?>" data-file-upload data-change-text="<?= esc($changeText, 'attr') ?>">
    <div class="file-upload__preview" aria-hidden="true">
        <?php if ($avatar): ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"<?= $current ? ' hidden' : '' ?>><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        <?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"<?= $current ? ' hidden' : '' ?>><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/></svg>
        <?php endif ?>
        <img src="<?= esc($current, 'attr') ?>" alt=""<?= $current ? '' : ' hidden' ?>>
    </div>
    <div class="file-upload__body">
        <input type="file" id="<?= esc($id, 'attr') ?>" name="<?= esc($name, 'attr') ?>" class="file-upload__input"
               accept="image/jpeg,image/png,image/gif,image/webp">
        <label for="<?= esc($id, 'attr') ?>" class="file-upload__btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            <span data-file-upload-btn><?= esc($btnText) ?></span>
        </label>
        <div class="file-upload__name" data-file-upload-name aria-live="polite"><?= esc($nameText) ?></div>
        <small class="file-upload__hint">JPG, PNG, GIF o WebP</small>
    </div>
</div>
