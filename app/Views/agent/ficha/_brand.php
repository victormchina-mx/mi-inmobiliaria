<?php
/**
 * Logo de la ficha técnica según la inmobiliaria del inmueble
 * (App\Libraries\Agency::brand()):
 * - Frices sin logo subido: su logo en SVG (agent/ficha/_logo.php).
 * - Con logo subido: la imagen, sobre una placa clara para que se lea en
 *   cualquier color de fondo.
 * - Independiente: logo de Inmobiliaria by VMCH (casa dorada).
 * - Sin logo: iniciales y nombre con los colores de la inmobiliaria.
 *
 * Variables: $brand, $id (único por plantilla)
 */
use App\Libraries\Agency;

$b = $brand ?? Agency::brand(null);
?>
<?php if ($b['frices']): ?>
<?= view('agent/ficha/_logo', ['id' => $id ?? 'logo']) ?>
<?php elseif ($b['logo']): ?>
<div class="fx-brand fx-brand--img"><img src="<?= esc($b['logo'], 'attr') ?>" alt="<?= esc($b['name'], 'attr') ?>"></div>
<?php elseif ($b['independent']): ?>
<div class="fx-logo fx-brand--inmo">
    <img class="fx-inmo__icon" src="<?= base_url('images/logo/inmobiliaria-icon.png') ?>" alt="">
    <div class="fx-logo__text"><b>Inmobiliaria</b><small>by VMCH</small></div>
</div>
<?php else: ?>
<div class="fx-logo fx-brand--text">
    <span class="fx-brand__ini"><?= esc(Agency::initials($b['name'])) ?></span>
    <div class="fx-logo__text">
        <b><?= esc(mb_strtoupper(Agency::shortName($b['name']))) ?></b>
        <?php if ($b['tagline'] !== ''): ?><span class="fx-logo__rule" aria-hidden="true"></span><small><?= esc($b['tagline']) ?></small><?php endif ?>
    </div>
</div>
<?php endif ?>
