<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Agentes</h2></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <?php foreach ($agents as $agent): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="agent-widget p-4 shadow-one text-center hover-shadow rounded">
                    <img src="<?= base_url('uploads/user/' . esc($agent['uimage'])) ?>"
                         alt="<?= esc($agent['uname']) ?>" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover;">
                    <h5 class="text-secondary text-capitalize"><?= esc($agent['uname']) ?></h5>
                    <p class="text-muted"><?= esc($agent['utype']) ?></p>
                    <ul class="list-unstyled text-left small">
                        <li><i class="fas fa-phone text-success mr-1"></i><?= esc($agent['uphone']) ?></li>
                        <li><i class="fas fa-envelope text-success mr-1"></i><?= esc($agent['uemail']) ?></li>
                    </ul>
                </div>
            </div>
            <?php endforeach ?>
            <?php if (empty($agents)): ?>
            <div class="col-12 text-center text-muted py-5">No hay agentes registrados.</div>
            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
