<?php
/**
 * Contenido del cuadro "Lo que hay cerca" (lo devuelve Property::nearby
 * dentro del JSON y public/js/nearby.js lo inserta en el detalle).
 *
 * Variables: $places (NearbyPlaces::parse), $counts, $summary
 * (NearbyPlaces::summary), $mapUrl, $level ('location'|'city'), $zone.
 * Estilos: .nb-* en public/css/style.css
 */
use App\Libraries\NearbyPlaces;

$categories = NearbyPlaces::CATEGORIES;
$total      = count($places);
$visible    = 6; // lugares que se ven antes de "Ver todos"

$nearest = [];
foreach ($places as $p) {
    $nearest[$p['cat']] ??= $p;
}
$distance = static fn (int $m): string => $m >= 1000 ? number_format($m / 1000, 1) . ' km' : $m . ' m';
?>
<div class="nb-head">
    <span class="nb-eyebrow">Alrededores</span>
    <h2 class="nb-title">Lo que hay cerca</h2>
    <p class="nb-sub">
        Lugares a menos de <?= NearbyPlaces::RADIUS / 1000 ?> km<?= $level === 'city' && $zone !== '' ? ' del centro de ' . esc($zone) : '' ?>, unos 15 minutos caminando
    </p>
    <a href="<?= esc($mapUrl, 'attr') ?>" class="nb-maplink"><i class="ms" aria-hidden="true">map</i>Verlos en el mapa <span aria-hidden="true">→</span></a>
</div>

<div class="nb-tabs" role="tablist" aria-label="Filtrar por tipo de lugar">
    <button type="button" class="nb-tab is-active" role="tab" aria-selected="true" data-nb-tab="all">
        <i class="ms" aria-hidden="true">apps</i>Todo <b><?= $total ?></b>
    </button>
    <?php foreach ($categories as $key => $cat): ?>
    <?php if ($counts[$key] > 0): ?>
    <button type="button" class="nb-tab" role="tab" aria-selected="false" data-nb-tab="<?= $key ?>" data-nb-label="<?= $cat['label'] ?>">
        <i class="ms" aria-hidden="true"><?= $cat['icon'] ?></i><?= $cat['label'] ?> <b><?= $counts[$key] ?></b>
    </button>
    <?php endif ?>
    <?php endforeach ?>
</div>

<div class="nb-grid">
    <div class="nb-cats">
        <?php foreach ($categories as $key => $cat): ?>
        <?php if ($key !== 'food' && $counts[$key] > 0): ?>
        <button type="button" class="nb-cat" style="--c:<?= $cat['color'] ?>" data-nb-tab="<?= $key ?>">
            <span class="nb-cat__ic" aria-hidden="true"><i class="ms"><?= $cat['icon'] ?></i></span>
            <span class="nb-cat__n"><?= $counts[$key] ?></span>
            <span class="nb-cat__l"><?= $cat['label'] ?></span>
            <span class="nb-cat__near"><?= esc($nearest[$key]['name']) ?> · <b><?= $nearest[$key]['min'] ?> min</b></span>
        </button>
        <?php endif ?>
        <?php endforeach ?>
    </div>

    <div class="nb-list">
        <div class="nb-list__head"><span data-nb-list-title>Más cercanos</span><span>Caminando</span></div>
        <ul data-nb-list data-nb-visible="<?= $visible ?>">
            <?php foreach ($places as $i => $p): ?>
            <?php $cat = $categories[$p['cat']]; ?>
            <li data-cat="<?= $p['cat'] ?>"<?= $i >= $visible ? ' hidden' : '' ?>>
                <span class="nb-dot" style="--c:<?= $cat['color'] ?>" aria-hidden="true"><i class="ms"><?= $cat['icon'] ?></i></span>
                <span class="nb-place"><b><?= esc($p['name']) ?></b><small><?= esc($p['type']) ?></small></span>
                <span class="nb-dist"><?= $distance($p['dist']) ?><small><?= $p['min'] ?> min</small></span>
            </li>
            <?php endforeach ?>
        </ul>
        <?php if ($total > $visible): ?>
        <button type="button" class="nb-more" data-nb-more aria-expanded="false">
            <span data-nb-more-label>Ver los <?= $total ?> lugares</span><i class="ms" aria-hidden="true">expand_more</i>
        </button>
        <?php endif ?>
    </div>
</div>

<?php if ($summary['text'] !== '' || $summary['tags']): ?>
<div class="nb-why">
    <div class="nb-why__intro">
        <span class="nb-eyebrow nb-eyebrow--light">¿Por qué vivir aquí?</span>
        <h3><?= esc($summary['title']) ?></h3>
        <?php if ($summary['text'] !== ''): ?><p><?= esc($summary['text']) ?></p><?php endif ?>
        <small class="nb-src"><i class="ms" aria-hidden="true">info</i>Datos de OpenStreetMap. Distancias aproximadas desde el centro de la zona.</small>
    </div>
    <?php if ($summary['tags']): ?>
    <ul class="nb-why__tags">
        <?php foreach ($summary['tags'] as $tag): ?>
        <li><span aria-hidden="true"><i class="ms"><?= $tag['icon'] ?></i></span><div><b><?= esc($tag['title']) ?></b><?= esc($tag['text']) ?></div></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?php else: ?>
<p class="nb-src nb-src--plain"><i class="ms" aria-hidden="true">info</i>Datos de OpenStreetMap. Distancias aproximadas desde el centro de la zona.</p>
<?php endif ?>
