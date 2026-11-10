<?php
/** Plantilla «Residencial»: horizontal 1500×1000, azul con collage e iconos. Datos: FichaTecnica::data() */
$photo = static fn (int $i) => isset($f['photos'][$i]) ? 'background-image:url(' . esc($f['photos'][$i], 'attr') . ')' : '';
$a     = $f['agent'];
?>
<div class="ficha ft-re" data-ficha-node data-w="1500" data-h="1000">
    <div class="ft-re__side">
        <img class="ft-re__logo" src="<?= base_url('images/logo/restatelg_white.png') ?>" alt="Real Estate">
        <div class="ft-re__script"><?= esc($f['type']) ?> en</div>
        <h2 class="ft-re__op"><?= esc(mb_strtoupper($f['operation'])) ?></h2>
        <?php if ($f['zone'] !== ''): ?><div class="ft-re__place"><?= esc($f['zone']) ?><?php if ($f['area'] !== ''): ?><small><?= esc($f['area']) ?></small><?php endif ?></div><?php endif ?>
        <ul class="ft-re__facts">
            <?php foreach ($f['facts'] as $x): ?><li><i class="ms"><?= $x['icon'] ?></i><?= esc($x['long']) ?></li><?php endforeach ?>
        </ul>
        <div class="ft-re__zone" data-zone-text><i class="ms">location_on</i><span>Ubicación: <?= esc($f['place']) ?></span></div>
    </div>
    <div class="ft-re__main">
        <div class="ft-re__img ft-re__big" style="<?= $photo(0) ?>"></div>
        <div class="ft-re__stack">
            <div class="ft-re__price"><small>PRECIO</small><b><?= esc($f['price']) ?></b><?php if ($f['isRent']): ?><small>AL MES</small><?php endif ?></div>
            <div class="ft-re__img" style="<?= $photo(1) ?>"></div>
            <div class="ft-re__img" style="<?= $photo(2) ?>"></div>
        </div>
        <div class="ft-re__near" data-near>
            <h4>LO QUE TIENE CERCA</h4>
            <div class="ft-re__near-items" data-near-items><span class="ft-re__near-empty">Escuelas, parques y servicios de la zona</span></div>
        </div>
        <div class="ft-re__cta">
            <div class="ft-qr ft-re__qr" data-qr></div>
            <div class="ft-re__who"><b><?= esc($a['name']) ?></b><span><?= esc($a['role']) ?></span><span>Ref. <?= esc($f['code']) ?></span>
                <?php if ($a['phone'] !== ''): ?><div class="ft-re__wa"><?= view('agent/ficha/_wa') ?><?= esc($a['phone']) ?></div><?php endif ?>
            </div>
        </div>
    </div>
</div>
