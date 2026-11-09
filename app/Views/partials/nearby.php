<?php
/**
 * Contenido del cuadro "Lo que hay cerca" (lo devuelve Property::nearby
 * dentro del JSON y public/js/nearby.js lo inserta en el detalle).
 *
 * Variables: $places (NearbyPlaces::parse), $counts, $summary
 * (NearbyPlaces::summary), $custom (texto del agente, App\Libraries\WhyText),
 * $mapUrl, $level ('exact'|'approx'|'location'|'city'|''), $zone.
 * Sin lugares ($places vacío) solo se muestra "¿Por qué vivir aquí?" con el
 * texto del agente.
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
$distance = [NearbyPlaces::class, 'distanceLabel'];
$custom   = $custom ?? '';

// Título del recuadro: el de los datos; sin lugares, el nombre de la zona
$whyTitle = $places ? $summary['title'] : ($zone !== '' ? 'Vivir en ' . $zone : 'La zona');
$whyText  = $custom !== '' ? $custom : $summary['text'];
?>
<?php if ($places): ?>
<div class="nb-head">
    <span class="nb-eyebrow">Alrededores</span>
    <h2 class="nb-title">Lo que hay cerca</h2>
    <p class="nb-sub">
        Lugares a menos de <?= NearbyPlaces::RADIUS / 1000 ?> km<?= match (true) {
            $level === 'exact'               => ' de la propiedad',
            $level === 'city' && $zone !== '' => ' del centro de ' . esc($zone),
            default                          => '',
        } ?>, unos 15 minutos caminando
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
            <span class="nb-cat__near"><?= esc($nearest[$key]['name']) ?> · <b><?= $nearest[$key]['dist'] < NearbyPlaces::STEPS ? 'a unos pasos' : $nearest[$key]['min'] . ' min' ?></b></span>
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
                <span class="nb-dist<?= $p['dist'] < NearbyPlaces::STEPS ? ' nb-dist--steps' : '' ?>"><?= $distance($p['dist']) ?><?php if ($p['dist'] >= NearbyPlaces::STEPS): ?><small><?= $p['min'] ?> min</small><?php endif ?></span>
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

<?php endif ?>

<?php if ($whyText !== '' || ($places && $summary['tags'])): ?>
<div class="nb-why<?= $places ? '' : ' nb-why--solo' ?>">
    <div class="nb-why__intro">
        <span class="nb-eyebrow nb-eyebrow--light">¿Por qué vivir aquí?</span>
        <h3><?= esc($whyTitle) ?></h3>
        <?php if ($whyText !== ''): ?><p><?= nl2br(esc($whyText), false) ?></p><?php endif ?>
        <?php if ($custom !== ''): ?>
        <small class="nb-src"><i class="ms" aria-hidden="true">person</i>Descripción del anunciante.<?= $places ? ' Lugares: datos de OpenStreetMap.' : '' ?></small>
        <?php else: ?>
        <small class="nb-src"><i class="ms" aria-hidden="true">info</i>Datos de OpenStreetMap. Distancias aproximadas desde <?= $level === 'exact' ? 'la propiedad' : 'el centro de la zona' ?>.</small>
        <?php endif ?>
    </div>
    <?php if ($places && $summary['tags']): ?>
    <ul class="nb-why__tags">
        <?php foreach ($summary['tags'] as $tag): ?>
        <li><span aria-hidden="true"><i class="ms"><?= $tag['icon'] ?></i></span><div><b><?= esc($tag['title']) ?></b><?= esc($tag['text']) ?></div></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?php elseif ($places): ?>
<p class="nb-src nb-src--plain"><i class="ms" aria-hidden="true">info</i>Datos de OpenStreetMap. Distancias aproximadas desde <?= $level === 'exact' ? 'la propiedad' : 'el centro de la zona' ?>.</p>
<?php endif ?>
