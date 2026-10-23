<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Breadcrumb -->
<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Propiedades</h2></div>
            <div class="col-md-6">
                <nav aria-label="breadcrumb" class="float-right">
                    <ol class="breadcrumb bg-transparent m-0 p-0">
                        <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-white">Inicio</a></li>
                        <li class="breadcrumb-item active text-white">Propiedades</li>
                    </ol>
                </nav>
            </div>
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
                            <span class="location"><i class="fas fa-map-marker-alt text-success"></i> <?= esc($p['location']) ?>, <?= esc($p['city']) ?></span>
                        </div>
                        <div class="bg-gray quantity px-4 pt-4">
                            <ul>
                                <li><span><?= esc($p['size']) ?></span> m²</li>
                                <li><span><?= esc($p['bedroom']) ?></span> Cuartos</li>
                                <li><span><?= esc($p['bathroom']) ?></span> Baños</li>
                                <li><span><?= esc($p['kitchen']) ?></span> Cocina</li>
                            </ul>
                        </div>
                        <div class="p-4 d-inline-block w-100">
                            <div class="float-left"><i class="fas fa-user text-success mr-1"></i><?= esc($p['uname']) ?></div>
                            <div class="float-right text-capitalize"><span class="badge badge-secondary"><?= esc($p['type']) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
            <?php if (empty($properties)): ?>
            <div class="col-12 text-center text-muted py-5">No hay propiedades disponibles.</div>
            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
