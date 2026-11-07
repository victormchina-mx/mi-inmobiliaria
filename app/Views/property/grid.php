<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h1 class="h2 page-name float-left text-white text-uppercase">Buscar Propiedades</h1></div>
        </div>
    </div>
</div>

<div class="full-row bg-gray">
    <div class="container">
        <form method="post" action="<?= site_url('property/grid') ?>">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-3">
                    <select class="form-control mb-2" name="type">
                        <option value="">Tipo de Propiedad</option>
                        <?php foreach (\App\Libraries\Labels::PROPERTY_TYPES as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($filters['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control mb-2" name="stype">
                        <option value="">Estado</option>
                        <option value="rent" <?= ($filters['stype'] ?? '') === 'rent' ? 'selected' : '' ?>>Renta</option>
                        <option value="sale" <?= ($filters['stype'] ?? '') === 'sale' ? 'selected' : '' ?>>Venta</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control mb-2" name="city" placeholder="Ciudad..." value="<?= esc($filters['city'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Buscar</button>
                </div>
            </div>
        </form>
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
                        <div class="bg-gray quantity px-4 pt-4">
                            <?= view('partials/property_stats', ['p' => $p]) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
            <?php if (empty($properties)): ?>
            <div class="col-12 text-center text-muted py-5">No se encontraron propiedades con esos criterios.</div>
            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
