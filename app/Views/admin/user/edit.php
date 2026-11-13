<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">Editar <?= esc($backLabel) ?></h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= site_url($backUrl) ?>"><?= esc($backLabel) ?></a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body">

                <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif ?>

                <form method="post" action="<?= site_url('admin/users/edit/' . $user['uid']) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre completo *</label>
                                <input type="text" name="uname" class="form-control"
                                       value="<?= esc(old('uname', $user['uname'])) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Correo electrónico *</label>
                                <input type="email" name="uemail" class="form-control"
                                       value="<?= esc(old('uemail', $user['uemail'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" name="uphone" class="form-control"
                                       value="<?= esc(old('uphone', $user['uphone'])) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de usuario *</label>
                                <select name="utype" class="form-control" required>
                                    <option value="user"    <?= old('utype', $user['utype']) === 'user'    ? 'selected' : '' ?>>Usuario</option>
                                    <option value="agent"   <?= old('utype', $user['utype']) === 'agent'   ? 'selected' : '' ?>>Agente</option>
                                    <option value="builder" <?= old('utype', $user['utype']) === 'builder' ? 'selected' : '' ?>>Constructor</option>
                                </select>
                            </div>
                            <?php if (\App\Libraries\Agency::ready()): ?>
                            <div class="form-group">
                                <label>Inmobiliaria</label>
                                <select name="agency_id" class="form-control">
                                    <option value="">Independiente (sin inmobiliaria)</option>
                                    <?php foreach (\App\Libraries\Agency::all() as $ag): ?>
                                    <option value="<?= (int) $ag['agency_id'] ?>" <?= (int) old('agency_id', (string) ($user['agency_id'] ?? '')) === (int) $ag['agency_id'] ? 'selected' : '' ?>><?= esc($ag['name']) ?></option>
                                    <?php endforeach ?>
                                </select>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="move-props" name="move_properties" value="1" checked>
                                    <label class="custom-control-label" for="move-props">Si cambia de inmobiliaria, mover también sus propiedades</label>
                                </div>
                            </div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                                <input type="password" name="upass" class="form-control"
                                       placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= view('partials/file_upload', [
                                    'name'     => 'uimage',
                                    'label'    => 'Foto de perfil',
                                    'current'  => ! empty($user['uimage']) ? base_url('uploads/user/' . $user['uimage']) : '',
                                    'avatar'   => true,
                                    'optional' => true,
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a href="<?= site_url($backUrl) ?>" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
