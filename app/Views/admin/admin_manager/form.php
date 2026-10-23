<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Agregar Administrador</h3></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/admins/create') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Usuario *</label>
                        <input type="text" name="auser" class="form-control" value="<?= esc(old('auser')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="aemail" class="form-control" value="<?= esc(old('aemail')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña *</label>
                        <input type="password" name="apass" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de nacimiento</label>
                        <input type="date" name="adob" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="aphone" class="form-control" maxlength="15">
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Administrador</button>
                    <a href="<?= site_url('admin/admins') ?>" class="btn btn-secondary ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
