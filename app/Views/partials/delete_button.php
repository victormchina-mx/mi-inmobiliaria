<?php
/**
 * Botón "Borrar" que envía un POST con token CSRF.
 *
 * Antes los borrados eran enlaces GET: bastaba con que alguien con sesión
 * abierta visitara una página con <img src=".../delete/1"> para borrar un
 * registro sin darse cuenta. Un POST con token no se puede falsificar así.
 *
 * Uso:
 *   <?= view('partials/delete_button', [
 *       'url'     => site_url('admin/states/delete/' . $s['sid']),
 *       'confirm' => '¿Eliminar?',   // mensaje de confirmación (opcional)
 *       'icon'    => 'fe fe-trash',  // clase del ícono (fe en admin, fas en el sitio)
 *   ]) ?>
 */
$confirm = $confirm ?? '¿Eliminar?';
$icon    = $icon ?? 'fe fe-trash';
?>
<form method="post" action="<?= esc($url, 'attr') ?>" class="d-inline"
      onsubmit="return confirm(<?= esc(json_encode($confirm, JSON_UNESCAPED_UNICODE), 'attr') ?>)">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-sm btn-danger text-white" title="Borrar" aria-label="Borrar"><i class="<?= esc($icon, 'attr') ?>"></i></button>
</form>
