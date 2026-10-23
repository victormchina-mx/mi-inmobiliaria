<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/style.css') ?>">
<title>Admin Login | Real Estate</title>
</head>
<body class="account-page">
<div class="main-wrapper">
    <div class="account-content">
        <div class="login-wrapper">
            <div class="loginbox">
                <div class="login-right">
                    <div class="login-right-wrap">
                        <img src="<?= base_url('admin/assets/img/rsadmin.png') ?>" alt="Logo" class="img-fluid mb-4" style="max-height:60px;">
                        <h1>Acceso Administrativo</h1>

                        <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif ?>

                        <form method="post" action="<?= site_url('admin/login') ?>">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label>Usuario o correo *</label>
                                <input type="text" name="auser" class="form-control"
                                       value="<?= esc(old('auser')) ?>" required autofocus>
                            </div>
                            <div class="form-group">
                                <label>Contraseña *</label>
                                <input type="password" name="apass" class="form-control" required>
                            </div>
                            <button class="btn btn-primary btn-block" type="submit">Iniciar Sesión</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="<?= site_url('admin/forgot-password') ?>">¿Olvidaste tu contraseña?</a>
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
