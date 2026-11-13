<?php
/**
 * Características de una propiedad con íconos (los mismos Material Symbols
 * que usa el detalle de la app móvil).
 *
 * Uso:
 *   <?= view('partials/property_stats', ['p' => $p]) ?>                 // tarjeta: m², recámaras, baños
 *   <?= view('partials/property_stats', ['p' => $p, 'full' => true]) ?> // detalle: + terreno, balcón, sala,
 *                                                                        //   cocina, estacionamientos, antigüedad
 *
 * Solo se muestran los datos que tiene la propiedad: un terreno no enseña
 * "0 recámaras, 0 baños", sino sus m² de terreno. Si no hay ningún dato no
 * se imprime nada.
 *
 * Los íconos se cargan en layouts/main.php (fuente "Material Symbols
 * Outlined" con solo los íconos usados) y se estilizan con la clase .ms.
 */
$full  = ! empty($full);
$num   = static fn (string $k): int => (int) ($p[$k] ?? 0);
$items = [];

if ($num('size') > 0) {
    $items[] = ['square_foot', $num('size'), $full && $num('land_size') > 0 ? 'm² constr.' : 'm²'];
}
if ($num('land_size') > 0 && ($full || $num('size') === 0)) {
    $items[] = ['landscape', $num('land_size'), 'm² terreno'];
}
if ($num('bedroom') > 0) {
    $items[] = ['bed', $num('bedroom'), 'Recámaras'];
}
if ($num('bathroom') > 0) {
    $items[] = ['bathtub', $num('bathroom'), 'Baños'];
}
if ($full) {
    foreach ([['balcony', 'balcony', 'Balcón'], ['weekend', 'hall', 'Sala'], ['countertops', 'kitchen', 'Cocina'], ['directions_car', 'parking', 'Estac.']] as [$icon, $key, $label]) {
        if ($num($key) > 0) {
            $items[] = [$icon, $num($key), $label];
        }
    }
    if (isset($p['age_years']) && $p['age_years'] !== '' && $p['age_years'] !== null) {
        $items[] = (int) $p['age_years'] === 0
            ? ['calendar_month', 'Nueva', 'A estrenar']
            : ['calendar_month', (int) $p['age_years'], (int) $p['age_years'] === 1 ? 'Año' : 'Años'];
    }
}

if (! $items) {
    return;
}
?>
<ul>
    <?php foreach ($items as [$icon, $value, $label]): ?>
    <li><span<?= $full ? ' class="text-secondary"' : '' ?>><i class="ms" aria-hidden="true"><?= $icon ?></i><?= esc($value) ?></span> <?= $label ?></li>
    <?php endforeach ?>
</ul>
