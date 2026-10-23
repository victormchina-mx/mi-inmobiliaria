<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Mi Perfil</h2></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 text-center">
                <?php if (! empty($user['uimage'])): ?>
                <img src="<?= base_url('uploads/user/' . esc($user['uimage'])) ?>" alt="Perfil"
                     class="rounded-circle mb-3" width="150" height="150" style="object-fit:cover;">
                <?php else: ?>
                <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:150px;height:150px;"><i class="fas fa-user fa-3x text-white"></i></div>
                <?php endif ?>
                <h4 class="text-secondary"><?= esc($user['uname']) ?></h4>
                <p class="text-muted text-capitalize"><?= esc($user['utype']) ?></p>
                <p><?= esc($user['uemail']) ?></p>
                <p><?= esc($user['uphone']) ?></p>
                <a href="<?= site_url('my-properties') ?>" class="btn btn-success btn-sm">Mis Propiedades</a>
                <a href="<?= site_url('my-properties/add') ?>" class="btn btn-outline-success btn-sm ml-1">+ Publicar</a>
            </div>
            <div class="col-lg-8">
                <h4 class="text-secondary mb-4">Enviar Testimonio</h4>
                <form method="post" action="<?= site_url('profile/feedback') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <textarea name="content" class="form-control" rows="4"
                                  placeholder="Comparte tu experiencia..." maxlength="300"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Enviar Testimonio</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
