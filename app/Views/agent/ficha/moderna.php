<?php
/** Plantilla «Moderna»: cuadrada 1080×1080 para redes, azul marino y dorado. Datos: FichaTecnica::data() */
$a = $f['agent'];
?>
<div class="ficha ft-mo" data-ficha-node data-w="1080" data-h="1080">
    <div class="ft-mo__photo" style="<?= isset($f['photos'][0]) ? 'background-image:url(' . esc($f['photos'][0], 'attr') . ')' : '' ?>"></div>
    <div class="ft-mo__shade"></div>
    <img class="ft-mo__logo" src="<?= base_url('images/logo/restatelg_white.png') ?>" alt="Real Estate">
    <div class="ft-mo__box">
        <small><?= esc(mb_strtoupper($f['type'] . ' en')) ?></small>
        <h2><?= esc(mb_strtoupper($f['operation'])) ?><?php if ($f['zone'] !== ''): ?><span><?= esc($f['zone']) ?></span><?php endif ?></h2>
        <?php if ($f['area'] !== ''): ?><p><i class="ms">location_on</i><?= esc($f['area']) ?></p><?php endif ?>
    </div>
    <div class="ft-mo__tag"><small>PRECIO</small><b><?= esc($f['price']) ?></b><?php if ($f['isRent']): ?><small>AL MES</small><?php endif ?></div>
    <div class="ft-mo__bottom">
        <div class="ft-mo__facts">
            <?php foreach (array_slice($f['facts'], 0, 6) as $x): ?>
            <div><i class="ms"><?= $x['icon'] ?></i><p><?= esc($x['value']) ?><small><?= esc($x['label']) ?></small></p></div>
            <?php endforeach ?>
        </div>
        <div class="ft-mo__contact">
            <div class="ft-mo__btn"><?= view('agent/ficha/_wa') ?>Agenda tu visita</div>
            <div class="ft-mo__row">
                <div class="ft-mo__who"><b><?= esc($a['name']) ?></b><span><?= esc($a['phone']) ?></span><span class="ft-mo__code"><?= esc($f['code']) ?></span></div>
                <?php if ($a['photo']): ?><img class="ft-mo__agent" src="<?= esc($a['photo']) ?>" alt=""><?php endif ?>
                <div class="ft-qr ft-mo__qr" data-qr></div>
            </div>
        </div>
    </div>
</div>
