<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-12"><h1 class="h2 page-name text-white text-uppercase">Propiedades en <?= esc($state) ?></h1></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <?php foreach ($properties as $p): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="featured-thumb hover-zoomer">
                    <div class="overlay-black overflow-hidden position-relative">
                        <img src="<?= esc(thumb_url('property', $p['pimage'], 800)) ?>" srcset="<?= esc(thumb_srcset('property', $p['pimage'])) ?>" sizes="(min-width: 992px) 350px, (min-width: 768px) 50vw, 100vw" alt="<?= esc($p['title']) ?>" loading="lazy" decoding="async">
                        <?= view('partials/agency_chip', ['p' => $p]) ?>
                        <div class="sale bg-success text-white text-capitalize"><i class="ms" aria-hidden="true"><?= $p['stype'] === 'sale' ? 'sell' : 'key' ?></i>Para <?= $p['stype'] === 'sale' ? 'Venta' : 'Renta' ?></div>
                        <div class="price text-primary"><b>$<?= number_format($p['price'], 0) ?></b></div>
                        <a href="<?= site_url('property/' . $p['pid']) ?>" class="thumb-link" tabindex="-1" aria-hidden="true"></a>
                    </div>
                    <div class="featured-thumb-data shadow-one">
                        <div class="p-3">
                            <h5 class="text-secondary hover-text-success mb-2">
                                <a href="<?= site_url('property/' . $p['pid']) ?>"><?= esc($p['title']) ?></a>
                            </h5>
                            <span class="location"><i class="fas fa-map-marker-alt text-success"></i> <?= esc($p['location']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
            <?php if (empty($properties)): ?>
            <div class="col-12 text-center text-muted py-5">No hay propiedades en este estado.</div>
            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
