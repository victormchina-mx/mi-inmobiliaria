<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h1 class="h2 page-name float-left text-white text-uppercase">Agentes</h1></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <?php foreach ($agents as $agent): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="agent-widget p-4 shadow-one text-center hover-shadow rounded">
                    <?php if (! empty($agent['uimage'])): ?>
                    <img src="<?= esc(thumb_url('user', $agent['uimage'], 200)) ?>" loading="lazy" decoding="async"
                         alt="<?= esc($agent['uname']) ?>" class="rounded-circle mb-3" width="100" height="100"
                         style="width:100px; height:100px; object-fit:cover;">
                    <?php else: ?>
                    <div class="rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center"
                         style="width:100px; height:100px; background:#F4F4F1; color:rgba(15,23,42,.35);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                    </div>
                    <?php endif ?>
                    <h5 class="text-secondary text-capitalize"><?= esc($agent['uname']) ?></h5>
                    <p class="text-muted"><?= esc(\App\Libraries\Labels::userType($agent['utype'])) ?></p>
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
