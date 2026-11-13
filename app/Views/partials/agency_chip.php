<?php
/**
 * Inmobiliaria de una propiedad en el portal (App\Libraries\Agency).
 *
 * Uso:
 *   <?= view('partials/agency_chip', ['p' => $p]) ?>                    // tarjeta: insignia sobre la foto
 *   <?= view('partials/agency_chip', ['p' => $p, 'band' => true]) ?>    // detalle: franja con logo y nombre
 *
 * $p viene de PropertyModel::withOwner() (agency_name, agency_logo,
 * agency_slug, agency_primary, agency_accent). Sin las tablas de
 * inmobiliarias no se muestra nada.
 */
use App\Libraries\Agency;

if (! Agency::ready()) {
    return;
}

$band    = ! empty($band);
$name    = (string) ($p['agency_name'] ?? '');
$primary = Agency::color($p['agency_primary'] ?? '') ?? Agency::NEUTRAL['primary'];
$accent  = Agency::color($p['agency_accent'] ?? '') ?? Agency::NEUTRAL['accent'];
$frices  = ($p['agency_slug'] ?? '') === 'frices' && empty($p['agency_logo']);
$logo    = ! empty($p['agency_logo']) ? Agency::logoUrl($p['agency_logo']) : null;
$icon    = ! empty($p['agency_logo']) ? Agency::iconUrl($p['agency_logo']) : null;   // recorte del círculo
$emblem  = '<svg viewBox="0 0 100 100" aria-hidden="true"><path d="M66.5 25 L24 50 H33.5 V72 H55 V58 H66 V67 Q66 71.5 70.5 70.5 C80 68 90 59 91.8 46.3 A42 42 0 0 0 50 8 A42 42 0 0 0 8 50 A42 42 0 0 0 50 92 A42 42 0 0 0 89.3 64.7" fill="none" stroke="' . $accent . '" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<?php if (! $band): ?>
<span class="ag-chip" title="<?= esc($name !== '' ? $name : 'Agente independiente', 'attr') ?>">
    <?php if ($name === ''): ?>
    <i class="ms ag-chip__ic" aria-hidden="true">person</i>Independiente
    <?php else: ?>
    <?php /* Con logo en imagen: círculo blanco (el color de fondo se asomaba como un aro oscuro) */ ?>
    <span class="ag-chip__mark<?= $icon ? ' has-img' : '' ?>"<?= $icon ? '' : ' style="background: ' . esc($primary, 'attr') . '; color: ' . esc($accent, 'attr') . ';"' ?>>
        <?php if ($icon): ?><img src="<?= esc($icon, 'attr') ?>" alt="" loading="lazy"<?= $icon !== $logo ? ' class="is-icon"' : '' ?>><?php elseif ($frices): ?><?= $emblem ?><?php else: ?><?= esc(Agency::initials($name)) ?><?php endif ?>
    </span><?= esc(Agency::shortName($name)) ?>
    <?php endif ?>
</span>
<?php elseif ($name !== ''): ?>
<div class="ag-band" style="background: <?= esc($primary, 'attr') ?>; color: <?= esc($accent, 'attr') ?>;">
    <?php if ($logo): ?>
    <span class="ag-band__plate"><img src="<?= esc($logo, 'attr') ?>" alt="<?= esc($name, 'attr') ?>" loading="lazy"></span>
    <?php else: ?>
    <span class="ag-band__mark"><?= $frices ? $emblem : esc(Agency::initials($name)) ?></span>
    <?php endif ?>
    <span class="ag-band__name<?= $frices ? ' is-frices' : '' ?>"><?= esc($name) ?></span>
</div>
<?php endif ?>
