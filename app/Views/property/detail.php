<?= $this->extend('layouts/main') ?>

<?php
// Fotos del carrusel (las que existan, en orden)
$galleryPhotos = array_values(array_filter(array_map(
    static fn (string $field) => $property[$field] ?? '',
    ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4']
)));
$gallerySizes = '(min-width: 1200px) 770px, (min-width: 992px) 610px, 100vw';
?>
<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?php if ($galleryPhotos): ?>
<?php // La primera foto es la imagen principal (LCP): se descarga primero, en el tamaño que elegirá el navegador ?>
<link rel="preload" as="image" imagesrcset="<?= esc(thumb_srcset('property', $galleryPhotos[0], [800, 1600])) ?>" imagesizes="<?= esc($gallerySizes) ?>" fetchpriority="high">
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/gallery.js') ?>"></script>
<script src="<?= asset_url('js/nearby.js') ?>" defer></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $schemaImages = [];
    foreach (['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4'] as $img) {
        if (! empty($property[$img])) {
            $schemaImages[] = base_url('uploads/property/' . $property[$img]);
        }
    }

    // Property descriptions are saved as rich text (TinyMCE) with no
    // server-side sanitization, so render them through an allowlist here
    // rather than trusting the stored HTML directly — otherwise anyone
    // who can publish a listing could inject a stored XSS payload.
    $safeDescription = \App\Libraries\HtmlSanitizer::clean(
        $property['pcontent'] ?? '',
        ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li']
    );
    // Las características también son HTML (columnas con listas): misma limpieza
    $safeFeature = \App\Libraries\HtmlSanitizer::clean($property['feature'] ?? '');

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'RealEstateListing',
        'name'        => $property['title'],
        'description' => strip_tags($property['pcontent'] ?? ''),
        'url'         => current_url(),
        'image'       => $schemaImages,
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $property['location'] ?? '',
            'addressLocality' => $property['city'] ?? '',
            'addressRegion'   => $property['state'] ?? '',
        ],
        'offers' => [
            '@type'        => 'Offer',
            'price'        => $property['price'] ?? '',
            'priceCurrency'=> 'MXN',
            'availability' => 'https://schema.org/InStock',
        ],
        'numberOfRooms' => $property['bedroom'] ?? '',
        'floorSize' => [
            '@type'    => 'QuantitativeValue',
            'value'    => $property['size'] ?? '',
            'unitCode' => 'MTK',
        ],
    ];
    // Coordenadas solo si el anunciante eligió mostrar la ubicación exacta
    $publicPoint = \App\Libraries\PropertyLocation::public($property);
    if (($publicPoint['level'] ?? null) === 'exact') {
        $schema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $publicPoint['lat'], 'longitude' => $publicPoint['lng']];
    }
?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Detalle de Propiedad</h2></div>
            <div class="col-md-6">
                <nav aria-label="breadcrumb" class="float-right">
                    <ol class="breadcrumb bg-transparent m-0 p-0">
                        <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-white">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= site_url('property') ?>" class="text-white">Propiedades</a></li>
                        <li class="breadcrumb-item active text-white"><?= esc($property['title']) ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <!-- Main column -->
            <div class="col-lg-8">
                <!-- Galería de imágenes -->
                <div class="row">
                    <div class="col-md-12">
                        <?php if ($galleryPhotos): ?>
                        <?php $total = count($galleryPhotos); ?>
                        <div class="gallery" data-gallery role="region" aria-roledescription="carrusel" aria-label="Fotos de <?= esc($property['title'], 'attr') ?>">
                            <div class="gallery__track" tabindex="0" aria-label="Desliza para ver las fotos">
                                <?php foreach ($galleryPhotos as $i => $photo): ?>
                                <figure class="gallery__slide" aria-label="Foto <?= $i + 1 ?> de <?= $total ?>">
                                    <?php
                                    $src    = esc(thumb_url('property', $photo, 1600));
                                    $srcset = esc(thumb_srcset('property', $photo, [800, 1600]));
                                    $alt    = esc($property['title']) . ' — foto ' . ($i + 1);
                                    ?>
                                    <?php if ($i === 0): ?>
                                    <img src="<?= $src ?>" srcset="<?= $srcset ?>" sizes="<?= esc($gallerySizes) ?>"
                                         width="1600" height="900" alt="<?= $alt ?>" fetchpriority="high" decoding="async">
                                    <?php else: ?>
                                    <?php // Se descarga al acercarse a ella (gallery.js); sin JS, la de <noscript> ?>
                                    <img data-src="<?= $src ?>" data-srcset="<?= $srcset ?>" sizes="<?= esc($gallerySizes) ?>"
                                         width="1600" height="900" alt="<?= $alt ?>" decoding="async">
                                    <noscript><img src="<?= $src ?>" srcset="<?= $srcset ?>" sizes="<?= esc($gallerySizes) ?>"
                                         width="1600" height="900" alt="<?= $alt ?>" loading="lazy"></noscript>
                                    <?php endif ?>
                                </figure>
                                <?php endforeach ?>
                            </div>
                            <?php if ($total > 1): ?>
                            <button type="button" class="gallery__nav gallery__nav--prev" data-gallery-prev aria-label="Foto anterior">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="gallery__nav gallery__nav--next" data-gallery-next aria-label="Foto siguiente">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                            </button>
                            <div class="gallery__count" aria-hidden="true"><span data-gallery-current>1</span> / <?= $total ?></div>
                            <div class="gallery__dots">
                                <?php for ($i = 0; $i < $total; $i++): ?>
                                <button type="button" data-gallery-dot aria-label="Ver foto <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
                                <?php endfor ?>
                            </div>
                            <?php endif ?>
                        </div>
                        <?php endif ?>
                    </div>
                </div>

                <!-- Info básica -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="bg-success d-table px-3 py-2 rounded text-white text-capitalize">
                            <i class="ms" aria-hidden="true"><?= $property['stype'] === 'sale' ? 'sell' : 'key' ?></i>Para <?= $property['stype'] === 'sale' ? 'Venta' : 'Renta' ?>
                        </div>
                        <h1 class="h5 mt-2 text-secondary text-capitalize"><?= esc($property['title']) ?></h1>
                        <?php $fullAddress = \App\Libraries\Geocoder::address($property); ?>
                        <?php if ($fullAddress !== ''): ?>
                        <a href="<?= site_url('property/' . $property['pid'] . '/ubicacion') ?>" class="loc-link" title="Ver la ubicación en el mapa">
                            <span class="loc-link__ic" aria-hidden="true"><i class="ms">location_on</i></span>
                            <span class="loc-link__txt text-capitalize"><?= esc($fullAddress) ?></span>
                            <span class="loc-link__cta">Ver mapa <span aria-hidden="true">→</span></span>
                        </a>
                        <?php endif ?>
                    </div>
                    <div class="col-md-6">
                        <div class="text-success h5 my-2 text-md-right">$<?= number_format($property['price'], 0) ?></div>
                        <div class="text-md-right">Precio</div>
                    </div>
                </div>

                <!-- Características -->
                <div class="property-details">
                    <div class="bg-gray property-quantity px-4 pt-4 w-100">
                        <?= view('partials/property_stats', ['p' => $property, 'full' => true]) ?>
                    </div>
<?php
                    // "2nd Floor" / "2 Floor" → 2: así se puede decir "Piso 2 de 2"
                    $floorNum = static fn ($v): ?int => preg_match('/\d+/', (string) $v, $m) ? (int) $m[0] : null;
                    $floor    = $floorNum($property['floor'] ?? '');
                    $floors   = $floorNum($property['totalfloor'] ?? '');
                    $floorText = match (true) {
                        $floor !== null && $floors !== null => $floor . ' de ' . $floors,
                        $floor !== null                     => (string) $floor,
                        $floors !== null                    => $floors . ($floors === 1 ? ' piso' : ' pisos'),
                        default                             => '',
                    };
                    ?>
                    <div class="facts">
                        <span><i class="ms" aria-hidden="true">home</i>Tipo: <b><?= esc(\App\Libraries\Labels::propertyType($property['type'])) ?></b></span>
                        <?php if ($floorText !== ''): ?>
                        <span><i class="ms" aria-hidden="true">stairs</i><?= $floor !== null ? 'Piso' : 'Edificio' ?>: <b><?= esc($floorText) ?></b></span>
                        <?php endif ?>
                    </div>

                    <!-- Secciones del detalle: todas con el mismo encabezado (.sec-head, igual que "Lo que hay cerca") -->
                    <?php if (trim(strip_tags($safeDescription)) !== ''): ?>
                    <section class="sec">
                    <div class="sec-head">
                        <span class="sec-eyebrow">La propiedad</span>
                        <h2 class="sec-title">Descripción</h2>
                    </div>
                        <div><?= $safeDescription ?></div>
                    </section>
                    <?php endif; ?>

                    <?php if (trim(strip_tags($safeFeature)) !== ''): ?>
                    <section class="sec">
                    <div class="sec-head">
                        <span class="sec-eyebrow">Detalles</span>
                        <h2 class="sec-title">Características</h2>
                    </div>
                        <div class="row"><?= $safeFeature ?></div>
                    </section>
                    <?php endif ?>

                    <!-- Planos (solo si hay alguno) -->
                    <?php if (! empty($property['mapimage']) || ! empty($property['topmapimage']) || ! empty($property['groundmapimage'])): ?>
                    <section class="sec">
                    <div class="sec-head">
                        <span class="sec-eyebrow">Distribución</span>
                        <h2 class="sec-title">Planos</h2>
                    </div>
                    <div class="accordion" id="accordionPlanos">
                        <?php if (! empty($property['mapimage'])): ?>
                        <button class="bg-gray py-3 px-4 mb-1 w-100 text-left rounded" type="button" data-toggle="collapse" data-target="#plano1">Plano Principal</button>
                        <div id="plano1" class="collapse show p-4">
                            <img src="<?= esc(thumb_url('property', $property['mapimage'], 1600)) ?>" alt="Plano" loading="lazy" decoding="async" class="img-fluid">
                        </div>
                        <?php endif ?>
                        <?php if (! empty($property['topmapimage'])): ?>
                        <button class="bg-gray py-3 px-4 mb-1 w-100 text-left rounded collapsed" type="button" data-toggle="collapse" data-target="#plano2">Planta Alta</button>
                        <div id="plano2" class="collapse p-4">
                            <img src="<?= esc(thumb_url('property', $property['topmapimage'], 1600)) ?>" alt="Planta alta" loading="lazy" decoding="async" class="img-fluid">
                        </div>
                        <?php endif ?>
                        <?php if (! empty($property['groundmapimage'])): ?>
                        <button class="bg-gray py-3 px-4 mb-1 w-100 text-left rounded collapsed" type="button" data-toggle="collapse" data-target="#plano3">Planta Baja</button>
                        <div id="plano3" class="collapse p-4">
                            <img src="<?= esc(thumb_url('property', $property['groundmapimage'], 1600)) ?>" alt="Planta baja" loading="lazy" decoding="async" class="img-fluid">
                        </div>
                        <?php endif ?>
                    </div>
                    </section>
                    <?php endif ?>

                    <!-- Lo que hay cerca: se carga después de la página (public/js/nearby.js, Property::nearby) -->
                    <section class="nb nb--col sec" data-nearby="<?= site_url('property/' . $property['pid'] . '/cerca') ?>" aria-busy="true" aria-label="Lo que hay cerca">
                        <div class="nb-head">
                            <span class="nb-eyebrow">Alrededores</span>
                            <h2 class="nb-title">Lo que hay cerca</h2>
                            <p class="nb-sub">Buscando escuelas, parques y servicios de la zona…</p>
                        </div>
                        <div class="nb-skeleton" aria-hidden="true">
                            <span></span><span></span><span></span><span></span><span></span><span></span>
                        </div>
                    </section>

                    <!-- Contacto con el agente -->
                    <section class="sec" id="contactar">
                    <div class="sec-head">
                        <span class="sec-eyebrow">¿Te interesa?</span>
                        <h2 class="sec-title">Contactar al agente</h2>
                    </div>
                    <?php
                    // Llamar, WhatsApp y correo, con un mensaje que ya menciona esta propiedad
                    $detailUrl  = site_url('property/' . $property['pid']);
                    $telHref    = \App\Libraries\ContactLinks::tel($property['uphone'] ?? '');
                    $waHref     = \App\Libraries\ContactLinks::whatsapp($property['uphone'] ?? '', $property['title'], $detailUrl);
                    $mailHref   = \App\Libraries\ContactLinks::mail($property['uemail'] ?? '', $property['title'], $detailUrl);
                    $agentName  = trim((string) ($property['uname'] ?? ''));
                    $initials   = mb_strtoupper(implode('', array_map(static fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/u', $agentName ?: '?'), 0, 2))));
                    ?>
                    <div class="agent-card">
                        <div class="agent-card__who">
                            <?php if (! empty($property['uimage'])): ?>
                            <img class="agent-card__photo" src="<?= esc(thumb_url('user', $property['uimage'], 200)) ?>" loading="lazy" decoding="async" alt="<?= esc($agentName) ?>" width="96" height="96">
                            <?php else: ?>
                            <span class="agent-card__photo agent-card__photo--initials" aria-hidden="true"><?= esc($initials) ?></span>
                            <?php endif ?>
                            <div class="agent-card__data">
                                <span class="agent-card__role"><?= esc(\App\Libraries\Labels::userType($property['utype'] ?? 'agent')) ?></span>
                                <h3 class="agent-card__name text-capitalize"><?= esc($agentName) ?></h3>
                                <ul class="agent-card__info">
                                    <?php if (! empty($property['uphone'])): ?>
                                    <li><i class="ms" aria-hidden="true">call</i><?= esc(\App\Libraries\ContactLinks::pretty($property['uphone'])) ?></li>
                                    <?php endif ?>
                                    <?php if (! empty($property['uemail'])): ?>
                                    <li><i class="ms" aria-hidden="true">mail</i><?= esc($property['uemail']) ?></li>
                                    <?php endif ?>
                                </ul>
                            </div>
                        </div>

                        <?php if ($telHref || $waHref || $mailHref): ?>
                        <div class="agent-card__actions">
                            <?php if ($telHref): ?>
                            <a class="ac-btn ac-btn--call" href="<?= esc($telHref, 'attr') ?>"><i class="ms" aria-hidden="true">call</i>Llamar</a>
                            <?php endif ?>
                            <?php if ($waHref): ?>
                            <a class="ac-btn ac-btn--wa" href="<?= esc($waHref, 'attr') ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.27-.2-.57-.35m-5.42 7.4h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41z"/></svg>WhatsApp
                            </a>
                            <?php endif ?>
                            <?php if ($mailHref): ?>
                            <a class="ac-btn ac-btn--mail" href="<?= esc($mailHref, 'attr') ?>"><i class="ms" aria-hidden="true">mail</i>Correo</a>
                            <?php endif ?>
                        </div>
                        <p class="agent-card__note"><i class="ms" aria-hidden="true">info</i>Por WhatsApp o correo, el mensaje ya incluye el nombre y el enlace de esta propiedad.</p>
                        <?php endif ?>
                    </div>
                    </section>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <h4 class="double-down-line-left text-secondary position-relative pb-4 my-4">Calculadora de Cuotas</h4>
                <form class="d-inline-block w-100" action="<?= site_url('calculator') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><div class="input-group-text">$</div></div>
                        <input type="number" class="form-control" name="amount" placeholder="Precio de la propiedad" value="<?= esc($property['price']) ?>">
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><div class="input-group-text"><i class="far fa-calendar-alt"></i></div></div>
                        <input type="number" class="form-control" name="month" placeholder="Plazo en meses">
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><div class="input-group-text">%</div></div>
                        <input type="number" step="0.01" class="form-control" name="interest" placeholder="Tasa de interés">
                    </div>
                    <button type="submit" class="btn btn-primary mt-4">Calcular Cuota</button>
                </form>

                <?php if (! empty($featured)): ?>
                <h4 class="double-down-line-left text-secondary position-relative pb-4 mb-4 mt-5">Propiedades Destacadas</h4>
                <ul class="property_list_widget">
                    <?php foreach ($featured as $fp): ?>
                    <li>
                        <img src="<?= esc(thumb_url('property', $fp['pimage'], 200)) ?>" alt="<?= esc($fp['title']) ?>" loading="lazy" decoding="async">
                        <h6 class="text-secondary hover-text-success text-capitalize">
                            <a href="<?= site_url('property/' . $fp['pid']) ?>"><?= esc($fp['title']) ?></a>
                        </h6>
                        <span class="font-14"><i class="fas fa-map-marker-alt text-success"></i> <?= esc($fp['location']) ?></span>
                    </li>
                    <?php endforeach ?>
                </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
