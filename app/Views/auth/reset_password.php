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
                        <h1>Nueva Contraseña</h1>
                        <p class="account-subtitle">Elige una nueva contraseña para tu cuenta.</p>

                        <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif ?>

                        <form method="post" action="<?= site_url('reset-password/' . esc($token)) ?>">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <input type="password" name="pass" class="form-control"
                                       placeholder="Nueva contraseña *" minlength="6" required autofocus>
                            </div>
                            <div class="form-group">
                                <input type="password" name="pass_confirm" class="form-control"
                                       placeholder="Confirmar contraseña *" minlength="6" required>
                            </div>
                            <button class="btn btn-success btn-block" type="submit">Cambiar contraseña</button>
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
