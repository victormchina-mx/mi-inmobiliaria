<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/login.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="page-wrappers login-body full-row bg-gray">
    <div class="login-wrapper">
        <div class="container">
            <div class="loginbox">
                <div class="login-right">
                    <div class="login-right-wrap">
                        <h1>Registro</h1>
                        <p class="account-subtitle">Crea tu cuenta</p>

                        <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif ?>

                        <form method="post" action="<?= site_url('register') ?>" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <input type="text" name="uname" class="form-control" placeholder="Nombre completo *"
                                       value="<?= esc(old('uname')) ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder="Correo electrónico *"
                                       value="<?= esc(old('email')) ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="phone" class="form-control" placeholder="Teléfono"
                                       value="<?= esc(old('phone')) ?>">
                            </div>
                            <div class="form-group">
                                <input type="password" name="pass" class="form-control" placeholder="Contraseña *" required>
                            </div>
                            <div class="form-group">
                                <label class="text-secondary">Tipo de usuario *</label>
                                <div>
                                    <label class="mr-3"><input type="radio" name="utype" value="user" <?= old('utype') === 'user' ? 'checked' : '' ?> required> Usuario</label>
                                    <label class="mr-3"><input type="radio" name="utype" value="agent" <?= old('utype') === 'agent' ? 'checked' : '' ?>> Agente</label>
                                    <label><input type="radio" name="utype" value="builder" <?= old('utype') === 'builder' ? 'checked' : '' ?>> Constructor</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="text-secondary d-block" for="uimage">Foto de perfil <span class="file-upload__optional">(opcional)</span></label>
                                <div class="file-upload" data-file-upload>
                                    <div class="file-upload__preview" aria-hidden="true">
                                        <i class="fas fa-user"></i>
                                        <img src="" alt="" hidden>
                                    </div>
                                    <div class="file-upload__body">
                                        <input type="file" id="uimage" name="uimage" class="file-upload__input"
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <label for="uimage" class="file-upload__btn">
                                            <i class="fas fa-camera mr-1"></i><span data-file-upload-btn>Elegir foto</span>
                                        </label>
                                        <div class="file-upload__name" data-file-upload-name aria-live="polite">Ningún archivo seleccionado</div>
                                        <small class="file-upload__hint">JPG, PNG, GIF o WebP</small>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-success" type="submit">Registrarme</button>
                        </form>

                        <div class="login-or"><span class="or-line"></span><span class="span-or">o</span></div>
                        <div class="text-center dont-have">¿Ya tienes cuenta? <a href="<?= site_url('login') ?>">Inicia sesión</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
