<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/style.css') ?>">
<title>Nueva Contraseña | Real Estate Admin</title>
</head>
<body class="account-page">
<div class="main-wrapper">
    <div class="account-content">
        <div class="login-wrapper">
            <div class="loginbox">
                <div class="login-right">
                    <div class="login-right-wrap">
                        <img src="<?= base_url('admin/assets/img/rsadmin.png') ?>" alt="Logo" class="img-fluid mb-4" style="max-height:60px;">
                        <h1>Nueva Contraseña</h1>
                        <p class="text-muted mb-4">Elige una nueva contraseña segura para tu cuenta.</p>

                        <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif ?>

                        <form method="post" action="<?= site_url('admin/reset-password/' . esc($token)) ?>">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label>Nueva contraseña *</label>
                                <input type="password" name="apass" class="form-control" minlength="6" required autofocus>
                            </div>
                            <div class="form-group">
                                <label>Confirmar contraseña *</label>
                                <input type="password" name="apass_confirm" class="form-control" minlength="6" required>
                            </div>
                            <button class="btn btn-primary btn-block" type="submit">Cambiar contraseña</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="<?= site_url('admin/login') ?>">← Volver al login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('admin/assets/js/jquery-3.2.1.min.js') ?>"></script>
<script src="<?= base_url('admin/assets/js/bootstrap.min.js') ?>"></script>
</body>
</html>
