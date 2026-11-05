<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col"><h3 class="page-title">Administradores</h3></div>
        <div class="col-auto">
            <a href="<?= site_url('admin/admins/create') ?>" class="btn btn-primary btn-rounded">
                <i class="fe fe-plus-circle"></i> Agregar Admin
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Usuario</th><th>Email</th><th>Teléfono</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($admins as $i => $a): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($a['auser']) ?></td>
                            <td><?= esc($a['aemail']) ?></td>
                            <td><?= esc($a['aphone']) ?></td>
                            <td>
                                <?= view('partials/delete_button', ['url' => site_url('admin/admins/delete/' . $a['aid']), 'confirm' => '¿Eliminar administrador?', 'icon' => 'fe fe-trash']) ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>$('[title]').tooltip();</script>
<?= $this->endSection() ?>
