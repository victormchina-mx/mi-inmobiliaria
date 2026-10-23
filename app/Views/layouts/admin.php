<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="shortcut icon" href="<?= base_url('images/favicon.ico') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/font-awesome.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/feathericon.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/style.css') ?>">
<?= $this->renderSection('styles') ?>
<title><?= esc($pageTitle ?? 'Admin') ?> | Panel de Administración</title>
</head>
<body>

<div class="main-wrapper">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <a href="<?= site_url('admin/dashboard') ?>" class="logo">
                <img src="<?= base_url('admin/assets/img/logo-small.png') ?>" alt="Logo" width="35" height="35">
                <span>Real Estate Admin</span>
            </a>
        </div>
        <ul class="nav user-menu">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fe fe-user"></i> <?= esc(session()->get('auser')) ?>
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="<?= site_url('admin/profile') ?>">Mi Perfil</a>
                    <a class="dropdown-item" href="<?= site_url('admin/logout') ?>">Cerrar Sesión</a>
                </div>
            </li>
        </ul>
    </div>
    <!-- /Header -->

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-inner slimscroll">
            <div id="sidebar-menu" class="sidebar-menu">
                <ul>
                    <li class="menu-title"><span>Principal</span></li>
                    <li><a href="<?= site_url('admin/dashboard') ?>"><i class="fe fe-home"></i> Dashboard</a></li>

                    <li class="submenu">
                        <a href="#"><i class="fe fe-users"></i> Usuarios <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="<?= site_url('admin/users') ?>">Usuarios</a></li>
                            <li><a href="<?= site_url('admin/agents') ?>">Agentes</a></li>
                            <li><a href="<?= site_url('admin/builders') ?>">Constructores</a></li>
                            <li><a href="<?= site_url('admin/admins') ?>">Administradores</a></li>
                        </ul>
                    </li>

                    <li class="submenu">
                        <a href="#"><i class="fe fe-map-pin"></i> Geografía <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="<?= site_url('admin/states') ?>">Estados</a></li>
                            <li><a href="<?= site_url('admin/cities') ?>">Ciudades</a></li>
                        </ul>
                    </li>

                    <li class="submenu">
                        <a href="#"><i class="fe fe-home"></i> Propiedades <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="<?= site_url('admin/property') ?>">Ver todas</a></li>
                            <li><a href="<?= site_url('admin/property/create') ?>">Agregar</a></li>
                        </ul>
                    </li>

                    <li class="submenu">
                        <a href="#"><i class="fe fe-message-square"></i> Consultas <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="<?= site_url('admin/contacts') ?>">Contactos</a></li>
                            <li><a href="<?= site_url('admin/feedback') ?>">Testimonios</a></li>
                        </ul>
                    </li>

                    <li><a href="<?= site_url('admin/about') ?>"><i class="fe fe-info"></i> Nosotros</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- /Sidebar -->

    <!-- Page Wrapper -->
    <div class="page-wrapper" style="margin-top: 60px;">
        <div class="content container-fluid">

            <!-- Flash messages -->
            <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif ?>
            <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif ?>

            <?= $this->renderSection('content') ?>
        </div>
    </div>
    <!-- /Page Wrapper -->
</div>

<script src="<?= base_url('admin/assets/js/jquery-3.2.1.min.js') ?>"></script>
<script src="<?= base_url('admin/assets/js/popper.min.js') ?>"></script>
<script src="<?= base_url('admin/assets/js/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('admin/assets/js/script.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
