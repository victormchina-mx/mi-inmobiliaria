<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="shortcut icon" type="image/png" href="/favicon.ico">
<link rel="shortcut icon" href="<?= base_url('images/favicon.ico') ?>">
<link href="https://fonts.googleapis.com/css?family=Muli:400,400i,500,600,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Comfortaa:400,700" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/bootstrap-slider.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/jquery-ui.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/layerslider.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/color.css') ?>" id="color-change">
<link rel="stylesheet" href="<?= base_url('css/owl.carousel.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/font-awesome.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('fonts/flaticon/flaticon.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
<?= $this->renderSection('styles') ?>
<title><?= esc($pageTitle ?? 'Real Estate') ?> | Real Estate by Vick</title>
</head>
<body>

<div id="page-wrapper">
    <div class="row">

        <!-- Header -->
        <header id="header" class="transparent-header-modern fixed-header-bg-white w-100">
            <div class="top-header bg-secondary">
                <div class="container">
                    <div class="row">
                        <div class="col-md-8">
                            <ul class="top-contact list-text-white d-table">
                                <li><a href="#"><i class="fas fa-phone-alt text-success mr-1"></i>+1 243-765-4321</a></li>
                                <li><a href="#"><i class="fas fa-envelope text-success mr-1"></i>info@realestate.com</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <div class="top-contact float-right">
                                <ul class="list-text-white d-table">
                                    <?php if (session()->get('uemail')): ?>
                                        <li><i class="fas fa-user text-success mr-1"></i>
                                            <a href="<?= site_url('logout') ?>">Logout</a>
                                        </li>
                                    <?php else: ?>
                                        <li><i class="fas fa-user text-success mr-1"></i>
                                            <a href="<?= site_url('login') ?>">Login</a>&nbsp;|&nbsp;
                                        </li>
                                        <li><i class="fas fa-user-plus text-success mr-1"></i>
                                            <a href="<?= site_url('register') ?>">Register</a>
                                        </li>
                                    <?php endif ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-nav secondary-nav hover-success-nav py-2">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <nav class="navbar navbar-expand-lg navbar-light p-0">
                                <a class="navbar-brand position-relative" href="<?= site_url('/') ?>">
                                    <img class="nav-logo" src="<?= base_url('images/logo/restatelg.png') ?>" alt="Logo">
                                </a>
                                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
                                    <span class="navbar-toggler-icon"></span>
                                </button>
                                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                    <ul class="navbar-nav mr-auto">
                                        <li class="nav-item"><a class="nav-link" href="<?= site_url('/') ?>">Inicio</a></li>
                                        <li class="nav-item"><a class="nav-link" href="<?= site_url('about') ?>">Nosotros</a></li>
                                        <li class="nav-item"><a class="nav-link" href="<?= site_url('contact') ?>">Contacto</a></li>
                                        <li class="nav-item"><a class="nav-link" href="<?= site_url('property') ?>">Propiedades</a></li>
                                        <li class="nav-item"><a class="nav-link" href="<?= site_url('agents') ?>">Agentes</a></li>
                                        <?php if (session()->get('uemail')): ?>
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Mi Cuenta</a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?= site_url('profile') ?>">Perfil</a></li>
                                                <li><a class="dropdown-item" href="<?= site_url('my-properties') ?>">Mis Propiedades</a></li>
                                                <li><a class="dropdown-item" href="<?= site_url('logout') ?>">Cerrar Sesión</a></li>
                                            </ul>
                                        </li>
                                        <?php else: ?>
                                        <li class="nav-item"><a class="nav-link" href="<?= site_url('login') ?>">Login / Registro</a></li>
                                        <?php endif ?>
                                    </ul>
                                    <a class="btn btn-success d-none d-xl-block" style="border-radius:30px;" href="<?= site_url('my-properties/add') ?>">Publicar Propiedad</a>
                                   <!-- <button class="btn btn-primary d-none d-xl-block ml-2" style="border-radius:30px;" data-toggle="modal" data-target="#modalCrearAdmin">Crear Admin</button>
                                    <a class="btn btn-secondary d-none d-xl-block ml-2" style="border-radius:30px;" href="<?// = site_url('admin/dashboard') ?>">Panel Admin</a> -->
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- /Header -->

        <!-- Flash messages -->
        <?php if (session()->getFlashdata('success')): ?>
        <div class="container mt-2"><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div></div>
        <?php endif ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="container mt-2"><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div></div>
        <?php endif ?>

        <?= $this->renderSection('content') ?>

        <!-- Footer -->
        <footer class="full-row bg-secondary p-0">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="divider py-40">
                            <div class="row">
                                <div class="col-md-12 col-lg-4">
                                    <div class="footer-widget mb-4">
                                        <div class="footer-logo mb-4">
                                            <a href="<?= site_url('/') ?>"><img class="logo-bottom" src="<?= base_url('images/logo/restatelg_white.png') ?>" alt="Logo"></a>
                                        </div>
                                        <p class="pb-20 text-white">Tu aliado de confianza en bienes raíces. Encuentra la propiedad ideal para ti.</p>
                                    </div>
                                </div>
                                <div class="col-md-12 col-lg-8">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="footer-widget footer-nav mb-4">
                                                <h4 class="widget-title text-white double-down-line-left position-relative">Soporte</h4>
                                                <ul class="hover-text-primary">
                                                    <li><a href="<?= site_url('contact') ?>" class="text-white">Contacto</a></li>
                                                    <li><a href="#" class="text-white">Términos y Condiciones</a></li>
                                                    <li><a href="#" class="text-white">Preguntas Frecuentes</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="footer-widget footer-nav mb-4">
                                                <h4 class="widget-title text-white double-down-line-left position-relative">Enlaces Rápidos</h4>
                                                <ul class="hover-text-primary">
                                                    <li><a href="<?= site_url('about') ?>" class="text-white">Nosotros</a></li>
                                                    <li><a href="<?= site_url('property') ?>" class="text-white">Propiedades</a></li>
                                                    <li><a href="<?= site_url('my-properties/add') ?>" class="text-white">Publicar Propiedad</a></li>
                                                    <li><a href="<?= site_url('agents') ?>" class="text-white">Nuestros Agentes</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="footer-widget">
                                                <h4 class="widget-title text-white double-down-line-left position-relative">Contáctanos</h4>
                                                <ul class="text-white">
                                                    <li><i class="fas fa-map-marker-alt text-white mr-2"></i>27 Ingram Street, Dayton</li>
                                                    <li><i class="fas fa-phone-alt text-white mr-2"></i>55 7606 2384</li>
                                                    <li><i class="fas fa-envelope text-white mr-2"></i>helpline@inmobiliaria.vmch.net</li>
                                                </ul>
                                                <div class="footer-widget media-widget mt-4 text-white hover-text-primary">
                                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row copyright">
                    <div class="col-sm-6">
                        <span class="text-white">© <?= date('Y') ?> Real Estate - Todos los derechos reservados</span>
                    </div>
                    <div class="col-sm-6">
                        <ul class="line-menu text-white hover-text-primary float-right">
                            <li><a href="#">Privacidad</a></li>
                            <li>|</li>
                            <li><a href="#">Mapa del Sitio</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
        <!-- /Footer -->

        <a href="#" class="bg-success text-white hover-text-secondary" id="scroll"><i class="fas fa-angle-up"></i></a>
    </div>
</div>

<!-- Modal Crear Admin -->
<div class="modal fade" id="modalCrearAdmin" tabindex="-1" role="dialog" aria-labelledby="modalCrearAdminLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearAdminLabel">Crear Administrador</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="<?= site_url('admin/register') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Usuario *</label>
                        <input type="text" name="auser" class="form-control" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="aemail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña * (mín. 6 caracteres)</label>
                        <input type="password" name="apass" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="aphone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="adob" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Modal Crear Admin -->

<script src="<?= base_url('js/jquery.min.js') ?>"></script>
<script src="<?= base_url('js/greensock.js') ?>"></script>
<script src="<?= base_url('js/layerslider.transitions.js') ?>"></script>
<script src="<?= base_url('js/layerslider.kreaturamedia.jquery.js') ?>"></script>
<script src="<?= base_url('js/popper.min.js') ?>"></script>
<script src="<?= base_url('js/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('js/owl.carousel.min.js') ?>"></script>
<script src="<?= base_url('js/jquery.dependClass-0.1.js') ?>"></script>
<script src="<?= base_url('js/draggable-0.1.js') ?>"></script>
<script src="<?= base_url('js/jquery.slider.js') ?>"></script>
<script src="<?= base_url('js/wow.js') ?>"></script>
<script src="<?= base_url('js/custom.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
