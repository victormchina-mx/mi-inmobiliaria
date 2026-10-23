<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Ciudades</h3></div></div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Agregar Ciudad</h4></div>
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/cities') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="sid" class="form-control" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($states as $s): ?>
                            <option value="<?= $s['sid'] ?>"><?= esc($s['sname']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ciudad *</label>
                        <input type="text" name="cname" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Ciudad</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($cities as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($c['cname']) ?></td>
                            <td><?= esc($c['sname']) ?></td>
                            <td>
                                <a href="<?= site_url('admin/cities/edit/' . $c['cid']) ?>" class="btn btn-sm btn-warning" title="Editar"><i class="fe fe-edit"></i></a>
                                <a href="<?= site_url('admin/cities/delete/' . $c['cid']) ?>" class="btn btn-sm btn-danger text-white"
                                   title="Borrar" onclick="return confirm('¿Eliminar?')"><i class="fe fe-trash"></i></a>
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
