<?= $this->extend('layouts/main') ?>

<?php
/**
 * Página "Ubicación" de una propiedad: mapa (Leaflet + OpenStreetMap) con
 * la zona aproximada, tarjeta de la propiedad y accesos a Google Maps/Waze.
 *
 * Controlador: Property::location · Ubicación en el mapa: App\Libraries\Geocoder
 * Estilos: .loc-* en public/css/style.css · Mapa: public/js/location.js
 *
 * $place es null si no se pudo ubicar la dirección: entonces no hay mapa,
 * solo el aviso y los enlaces (Google Maps busca el texto por su cuenta).
 */
$detailUrl  = site_url('property/' . $property['pid']);
$searchText = $address . ', México';
$gmapsUrl   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($searchText);
$routeUrl   = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($searchText);
$wazeUrl    = $place
    ? 'https://waze.com/ul?ll=' . $place['lat'] . ',' . $place['lng'] . '&navigate=yes'
    : 'https://waze.com/ul?q=' . rawurlencode($searchText) . '&navigate=yes';

$level = $place['level'] ?? null;
$zoneLabel = match ($level) {
    'location' => 'Zona aproximada',
    'city'     => 'Ciudad / municipio',
    'state'    => 'Estado',
    default    => '',
};
$noteText = match ($level) {
    'location' => 'Por seguridad mostramos la zona de la propiedad, no el domicilio exacto. El anunciante te comparte la dirección y agenda la visita cuando lo contactas.',
    'city'     => 'Mostramos la ciudad o municipio donde se encuentra la propiedad. El anunciante te comparte la dirección exacta y agenda la visita cuando lo contactas.',
    'state'    => 'Mostramos el estado donde se encuentra la propiedad. El anunciante te comparte la dirección exacta y agenda la visita cuando lo contactas.',
    default    => 'No pudimos ubicar esta dirección en el mapa. Puedes buscarla en Google Maps o pedirle la ubicación al anunciante.',
};

// Encabezado: "Popotla" y debajo "Miguel Hidalgo, CDMX"
$addressParts = explode(', ', $address);
$addressMain  = array_shift($addressParts) ?? '';
$addressRest  = implode(', ', $addressParts);

$isSale = $property['stype'] === 'sale';
$facts  = array_filter([
    'Colonia / zona'     => trim((string) $property['location']),
    'Ciudad / municipio' => trim((string) $property['city']),
    'Estado'             => trim((string) $property['state']),
], static fn ($v) => $v !== '');
?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?php if ($place): ?>
<link rel="stylesheet" href="<?= asset_url('lib/leaflet/leaflet.css') ?>">
<link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($place): ?>
<script src="<?= asset_url('lib/leaflet/leaflet.js') ?>"></script>
<?php endif ?>
<script src="<?= asset_url('js/location.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h1 class="h2 page-name float-left text-white text-uppercase">Ubicación</h1></div>
            <div class="col-md-6">
                <nav aria-label="breadcrumb" class="float-right">
                    <ol class="breadcrumb bg-transparent m-0 p-0">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>" class="text-white">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= site_url('property') ?>" class="text-white">Propiedades</a></li>
                        <li class="breadcrumb-item"><a href="<?= $detailUrl ?>" class="text-white"><?= esc($property['title']) ?></a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Ubicación</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="full-row loc-page">
    <div class="container">
        <div class="loc-head">
            <a href="<?= $detailUrl ?>" class="loc-back"><i class="ms" aria-hidden="true">arrow_back</i>Volver a la propiedad</a>
            <?php if ($address !== ''): ?>
            <div class="loc-address">
                <span class="loc-address__pin" aria-hidden="true"><i class="ms">location_on</i></span>
                <div>
                    <strong class="text-capitalize"><?= esc($addressMain) ?></strong>
                    <?php if ($addressRest !== ''): ?><span class="text-capitalize"><?= esc($addressRest) ?></span><?php endif ?>
                </div>
            </div>
            <?php endif ?>
        </div>

        <div class="loc-map<?= $place ? '' : ' loc-map--empty' ?>" data-loc-wrap>
            <?php if ($place): ?>
            <div class="loc-map__canvas" id="loc-map" role="region" aria-label="Mapa de la ubicación"
                 data-lat="<?= esc((string) $place['lat'], 'attr') ?>" data-lng="<?= esc((string) $place['lng'], 'attr') ?>"
                 data-level="<?= esc($level, 'attr') ?>" data-label="<?= esc($zoneLabel, 'attr') ?>"
                 data-bbox="<?= esc(json_encode($place['bbox']), 'attr') ?>"></div>
            <?php else: ?>
            <div class="loc-map__canvas loc-empty">
                <span class="loc-empty__ic" aria-hidden="true"><i class="ms">map</i></span>
                <p class="loc-empty__title">Mapa no disponible</p>
                <p class="loc-empty__text">No encontramos esta dirección en el mapa.</p>
                <a href="<?= esc($gmapsUrl, 'attr') ?>" class="loc-btn loc-btn--dark" target="_blank" rel="noopener">Buscar en Google Maps</a>
            </div>
            <?php endif ?>

            <article class="loc-card">
                <?php if (! empty($property['pimage'])): ?>
                <a href="<?= $detailUrl ?>" class="loc-card__img" tabindex="-1" aria-hidden="true">
                    <img src="<?= esc(thumb_url('property', $property['pimage'], 800)) ?>" alt="" width="800" height="450" decoding="async">
                    <span class="loc-card__badge"><i class="ms"><?= $isSale ? 'sell' : 'key' ?></i>Para <?= $isSale ? 'Venta' : 'Renta' ?></span>
                </a>
                <?php endif ?>
                <div class="loc-card__body">
                    <div class="loc-card__price">$<?= number_format((float) $property['price'], 0) ?> <small>MXN</small></div>
                    <h2 class="loc-card__title text-capitalize"><a href="<?= $detailUrl ?>"><?= esc($property['title']) ?></a></h2>
                    <?php if ($address !== ''): ?>
                    <p class="loc-card__addr text-capitalize"><i class="ms" aria-hidden="true">location_on</i><?= esc($address) ?></p>
                    <?php endif ?>
                    <ul class="loc-card__stats">
                        <li><i class="ms" aria-hidden="true">square_foot</i><?= esc($property['size']) ?> m²</li>
                        <li><i class="ms" aria-hidden="true">bed</i><?= esc($property['bedroom']) ?> Rec.</li>
                        <li><i class="ms" aria-hidden="true">bathtub</i><?= esc($property['bathroom']) ?> Baños</li>
                    </ul>
                    <div class="loc-card__btns">
                        <a href="<?= esc($routeUrl, 'attr') ?>" class="loc-btn loc-btn--gold" target="_blank" rel="noopener"><i class="ms" aria-hidden="true">directions</i>Cómo llegar</a>
                        <a href="<?= $detailUrl ?>" class="loc-btn loc-btn--ghost">Ver propiedad</a>
                    </div>
                </div>
            </article>
        </div>

        <div class="loc-actions">
            <a href="<?= esc($gmapsUrl, 'attr') ?>" class="loc-chip" target="_blank" rel="noopener">
                <span class="loc-chip__ic loc-chip__ic--google" aria-hidden="true"><i class="ms">map</i></span>Abrir en Google Maps
            </a>
            <a href="<?= esc($wazeUrl, 'attr') ?>" class="loc-chip" target="_blank" rel="noopener">
                <span class="loc-chip__ic loc-chip__ic--waze" aria-hidden="true"><i class="ms">navigation</i></span>Abrir en Waze
            </a>
            <button type="button" class="loc-chip" data-copy="<?= esc($address, 'attr') ?>">
                <span class="loc-chip__ic" aria-hidden="true"><i class="ms">content_copy</i></span>Copiar dirección
            </button>
            <button type="button" class="loc-chip" data-share data-share-title="<?= esc($property['title'], 'attr') ?>">
                <span class="loc-chip__ic" aria-hidden="true"><i class="ms">share</i></span>Compartir
            </button>
        </div>

        <div class="loc-grid">
            <?php if ($facts): ?>
            <div class="loc-facts">
                <h3>Datos de la zona</h3>
                <dl>
                    <?php foreach ($facts as $label => $value): ?>
                    <div><dt><?= $label ?></dt><dd class="text-capitalize"><?= esc($value) ?></dd></div>
                    <?php endforeach ?>
                </dl>
            </div>
            <?php endif ?>
            <div class="loc-note">
                <i class="ms" aria-hidden="true">info</i>
                <div>
                    <strong><?= $place ? 'Ubicación aproximada' : 'Ubicación no disponible' ?></strong>
                    <p><?= $noteText ?></p>
                    <a href="<?= $detailUrl ?>#contactar" class="loc-btn loc-btn--dark"><i class="ms" aria-hidden="true">mail</i>Contactar al anunciante</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loc-sticky">
    <a href="<?= esc($routeUrl, 'attr') ?>" class="loc-btn loc-btn--gold" target="_blank" rel="noopener"><i class="ms" aria-hidden="true">directions</i>Cómo llegar</a>
    <button type="button" class="loc-btn loc-btn--ghost" data-share data-share-title="<?= esc($property['title'], 'attr') ?>" aria-label="Compartir"><i class="ms" aria-hidden="true">share</i></button>
</div>

<div class="loc-toast" role="status" aria-live="polite" data-toast></div>

<?= $this->endSection() ?>
