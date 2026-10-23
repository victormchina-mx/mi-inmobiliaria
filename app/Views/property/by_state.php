<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-12"><h2 class="page-name text-white text-uppercase">Propiedades en <?= esc($state) ?></h2></div>
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
                        <img src="<?= base_url('uploads/property/' . esc($p['pimage'])) ?>" alt="<?= esc($p['title']) ?>">
                        <div class="sale bg-success text-white text-capitalize">Para <?= $p['stype'] === 'sale' ? 'Venta' : 'Renta' ?></div>
                        <div class="price text-primary"><b>$<?= number_format($p['price'], 0) ?></b></div>
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
