<?php
/**
 * Logo de la ficha técnica según la inmobiliaria del inmueble
 * (App\Libraries\Agency::brand()):
 * - Frices sin logo subido: su logo en SVG (agent/ficha/_logo.php).
 * - Con logo subido: la imagen, sobre una placa clara para que se lea en
 *   cualquier color de fondo.
 * - Independiente: logo de Real Estate by Vick.
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
<div class="fx-brand fx-brand--vick"><img src="<?= base_url('images/logo/restatelg_white.png') ?>" alt="Real Estate by Vick"><span>by Vick</span></div>
<?php else: ?>
<div class="fx-logo fx-brand--text">
    <span class="fx-brand__ini"><?= esc(Agency::initials($b['name'])) ?></span>
    <div class="fx-logo__text">
        <b><?= esc(mb_strtoupper(Agency::shortName($b['name']))) ?></b>
        <?php if ($b['tagline'] !== ''): ?><span class="fx-logo__rule" aria-hidden="true"></span><small><?= esc($b['tagline']) ?></small><?php endif ?>
    </div>
</div>
<?php endif ?>
