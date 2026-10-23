<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Editar Estado</h3></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/states/edit/' . $state['sid']) ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="sname" class="form-control" value="<?= esc($state['sname']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?= site_url('admin/states') ?>" class="btn btn-secondary ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
