<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Nosotros</h2></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <?php if ($about): ?>
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="text-secondary mb-4"><?= esc($about['title']) ?></h2>
                <div><?= $about['content'] ?></div>
            </div>
            <?php if (! empty($about['image'])): ?>
            <div class="col-lg-6 text-center">
                <img src="<?= base_url('uploads/about/' . esc($about['image'])) ?>" alt="<?= esc($about['title']) ?>" class="img-fluid rounded shadow">
            </div>
            <?php endif ?>
        </div>
        <?php else: ?>
        <p class="text-center text-muted py-5">Información no disponible.</p>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
