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
                        <h1>Recuperar Contraseña</h1>
                        <p class="account-subtitle">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

                        <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif ?>
                        <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                        <?php endif ?>

                        <form method="post" action="<?= site_url('forgot-password') ?>">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <input type="email" name="email" class="form-control"
                                       placeholder="Correo electrónico *"
                                       value="<?= esc(old('email')) ?>" required autofocus>
                            </div>
                            <button class="btn btn-success btn-block" type="submit">Enviar instrucciones</button>
                        </form>

                        <div class="login-or"><span class="or-line"></span></div>
                        <div class="text-center dont-have">
                            <a href="<?= site_url('login') ?>">← Volver al login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
