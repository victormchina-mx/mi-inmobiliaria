<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Buscar Propiedades</h2></div>
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
                        <?php foreach (['apartment'=>'Departamento','flat'=>'Flat','building'=>'Edificio','house'=>'Casa','villa'=>'Villa','office'=>'Oficina'] as $val => $label): ?>
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
                        <div class="bg-gray quantity px-4 pt-4">
                            <ul>
                                <li><span><?= esc($p['size']) ?></span> m²</li>
                                <li><span><?= esc($p['bedroom']) ?></span> Cuartos</li>
                                <li><span><?= esc($p['bathroom']) ?></span> Baños</li>
                            </ul>
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
