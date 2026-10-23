<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Editar Ciudad</h3></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/cities/edit/' . $city['cid']) ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="sid" class="form-control" required>
                            <?php foreach ($states as $s): ?>
                            <option value="<?= $s['sid'] ?>" <?= $city['sid'] == $s['sid'] ? 'selected' : '' ?>><?= esc($s['sname']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ciudad *</label>
                        <input type="text" name="cname" class="form-control" value="<?= esc($city['cname']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?= site_url('admin/cities') ?>" class="btn btn-secondary ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
