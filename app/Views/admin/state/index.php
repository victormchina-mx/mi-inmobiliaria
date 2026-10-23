<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Estados/Provincias</h3></div></div>
</div>

<div class="row">
    <!-- Formulario para agregar -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Agregar Estado</h4></div>
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/states') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="sname" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($states as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($s['sname']) ?></td>
                            <td>
                                <a href="<?= site_url('admin/states/edit/' . $s['sid']) ?>" class="btn btn-sm btn-warning"><i class="fe fe-edit"></i></a>
                                <a href="<?= site_url('admin/states/delete/' . $s['sid']) ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('¿Eliminar?')"><i class="fe fe-trash-2"></i></a>
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
