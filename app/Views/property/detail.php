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

                    <?php if (trim(strip_tags($safeDescription)) !== ''): ?>
                    <h4 class="text-secondary my-4">Descripción</h4>
                    <div><?= $safeDescription ?></div>
                    <?php endif; ?>

                    <h5 class="mt-5 mb-4 text-secondary">Resumen de la Propiedad</h5>
                    <div class="table-striped font-14 pb-2">
                        <table class="w-100">
                            <tbody>
                                <tr>
                                    <td>BHK:</td><td class="text-capitalize"><?= esc($property['bhk']) ?></td>
                                    <td>Tipo:</td><td><?= esc(\App\Libraries\Labels::propertyType($property['type'])) ?></td>
                                </tr>
                                <tr>
                                    <td>Piso:</td><td class="text-capitalize"><?= esc($property['floor']) ?></td>
                                    <td>Total pisos:</td><td class="text-capitalize"><?= esc($property['totalfloor']) ?></td>
                                </tr>
                                <tr>
                                    <td>Ciudad:</td><td class="text-capitalize"><?= esc($property['city']) ?></td>
                                    <td>Estado:</td><td class="text-capitalize"><?= esc($property['state']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (trim(strip_tags($safeFeature)) !== ''): ?>
                    <h5 class="mt-5 mb-4 text-secondary">Características</h5>
                    <div class="row"><?= $safeFeature ?></div>
                    <?php endif ?>

                    <!-- Planos -->
                    <h5 class="mt-5 mb-4 text-secondary">Planos</h5>
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

                    <!-- Contacto con el agente -->
                    <h5 class="mt-5 mb-4 text-secondary double-down-line-left position-relative" id="contactar">Contactar Agente</h5>
                    <div class="agent-contact">
                        <div class="row">
                            <div class="col-sm-4 col-lg-3">
                                <img src="<?= esc(thumb_url('user', $property['uimage'], 200)) ?>" loading="lazy" decoding="async" alt="<?= esc($property['uname']) ?>" height="200" width="170">
                            </div>
                            <div class="col-sm-8 col-lg-9">
                                <div class="agent-data mt-sm-20">
                                    <h6 class="text-success text-capitalize"><?= esc($property['uname']) ?></h6>
                                    <ul class="mb-3">
                                        <li><?= esc($property['uphone']) ?></li>
                                        <li><?= esc($property['uemail']) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
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
