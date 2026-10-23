<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Mi Perfil</h3></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <?php if ($admin): ?>
                <table class="table table-borderless">
                    <tr><th>Usuario:</th><td><?= esc($admin['auser']) ?></td></tr>
                    <tr><th>Email:</th><td><?= esc($admin['aemail']) ?></td></tr>
                    <tr><th>Teléfono:</th><td><?= esc($admin['aphone']) ?></td></tr>
                    <tr><th>Fecha de nac.:</th><td><?= esc($admin['adob']) ?></td></tr>
                </table>
                <?php else: ?>
                <p class="text-muted">Información no disponible.</p>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
