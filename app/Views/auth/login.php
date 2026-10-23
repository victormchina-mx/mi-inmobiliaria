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
                        <h1>Iniciar Sesión</h1>
                        <p class="account-subtitle">Accede a tu cuenta</p>

                        <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif ?>
                        <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                        <?php endif ?>

                        <form method="post" action="<?= site_url('login') ?>">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder="Correo electrónico *"
                                       value="<?= esc(old('email')) ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="password" name="pass" class="form-control" placeholder="Contraseña *" required>
                            </div>
                            <button class="btn btn-success" type="submit">Iniciar Sesión</button>
                        </form>

                        <div class="text-center mt-2 mb-2">
                            <a href="<?= site_url('forgot-password') ?>">¿Olvidaste tu contraseña?</a>
                        </div>
                        <div class="login-or"><span class="or-line"></span><span class="span-or">o</span></div>
                        <div class="text-center dont-have">¿No tienes cuenta? <a href="<?= site_url('register') ?>">Regístrate</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
