<?php
/** Plantilla «Elegante»: vertical tamaño carta, azul marino con dorado (colores Frices). Datos: FichaTecnica::data() */
$photo = static fn (int $i) => isset($f['photos'][$i]) ? 'background-image:url(' . esc($f['photos'][$i], 'attr') . ')' : '';
$a     = $f['agent'];
?>
<div class="ficha ft-el" data-ficha-node data-w="850" data-h="1100">
    <div class="ft-el__photo" style="<?= $photo(0) ?>"></div>
    <div class="ft-el__band"></div>
    <div class="ft-el__logo"><?= view('agent/ficha/_logo', ['id' => 'elegante']) ?></div>
    <span class="ft-el__code"><?= esc($f['code']) ?></span>
    <div class="ft-el__head">
        <h2 class="ft-el__title"><?= esc($f['type']) ?> en <em><?= esc($f['operation']) ?></em><?php if ($f['zone'] !== ''): ?><span><?= esc($f['zone']) ?></span><?php endif ?></h2>
        <?php if ($f['place'] !== ''): ?>
        <p class="ft-el__zone"><i class="ms">location_on</i><?= esc($f['place']) ?></p>
        <?php endif ?>
    </div>
    <div class="ft-el__thumbs">
        <?php for ($i = 1; $i <= 2; $i++): ?>
        <?php if (isset($f['photos'][$i])): ?><div style="<?= $photo($i) ?>"></div><?php endif ?>
        <?php endfor ?>
    </div>
    <div class="ft-el__price"><small>PRECIO</small><b><?= esc($f['price']) ?></b><small><?= esc($f['priceNote']) ?></small></div>
    <?php if ($f['facts']): ?>
    <div class="ft-el__facts"><h3>CARACTERÍSTICAS</h3><ul>
        <?php foreach ($f['facts'] as $x): ?><li><i class="ms"><?= $x['icon'] ?></i><?= esc($x['value'] . ' ' . mb_strtolower($x['label'])) ?></li><?php endforeach ?>
    </ul></div>
    <?php endif ?>
    <div class="ft-el__cta">
        <?php if ($a['photo']): ?><img class="ft-el__agent" src="<?= esc($a['photo']) ?>" alt=""><?php endif ?>
        <div class="ft-el__who"><b><?= esc($a['name']) ?></b><span><?= esc($a['role']) ?></span></div>
        <?php if ($a['phone'] !== ''): ?><div class="ft-el__wa"><?= view('agent/ficha/_wa') ?><?= esc($a['phone']) ?></div><?php endif ?>
        <div class="ft-qr ft-el__qr" data-qr></div>
    </div>
</div>
