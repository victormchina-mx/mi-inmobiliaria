<?php
/**
 * Características de una propiedad con íconos (los mismos Material Symbols
 * que usa el detalle de la app móvil).
 *
 * Uso:
 *   <?= view('partials/property_stats', ['p' => $p]) ?>                 // tarjeta: m², recámaras, baños
 *   <?= view('partials/property_stats', ['p' => $p, 'full' => true]) ?> // detalle: + balcón, sala, cocina
 *
 * Los íconos se cargan en layouts/main.php (fuente "Material Symbols
 * Outlined" con solo los íconos usados) y se estilizan con la clase .ms.
 */
$full  = ! empty($full);
$items = [
    ['square_foot', $p['size'] ?? 0, 'm²'],
    ['bed', $p['bedroom'] ?? 0, 'Recámaras'],
    ['bathtub', $p['bathroom'] ?? 0, 'Baños'],
];
if ($full) {
    $items[] = ['balcony', $p['balcony'] ?? 0, 'Balcón'];
    $items[] = ['weekend', $p['hall'] ?? 0, 'Sala'];
    $items[] = ['countertops', $p['kitchen'] ?? 0, 'Cocina'];
}
?>
<ul>
    <?php foreach ($items as [$icon, $value, $label]): ?>
    <li><span<?= $full ? ' class="text-secondary"' : '' ?>><i class="ms" aria-hidden="true"><?= $icon ?></i><?= esc($value) ?></span> <?= $label ?></li>
    <?php endforeach ?>
</ul>
