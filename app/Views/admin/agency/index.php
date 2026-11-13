<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
/**
 * Admin → Inmobiliarias (Controlador: Admin\Agency · Reglas: App\Libraries\Agency)
 */
use App\Libraries\Agency;
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">Inmobiliarias</h3>
            <p class="text-muted mb-0">Cada inmobiliaria tiene su logo, sus colores y sus agentes. Sus agentes solo ven lo de su inmobiliaria.</p>
        </div>
        <?php if ($ready): ?>
        <div class="col-auto">
            <a href="<?= site_url('admin/agencies/create') ?>" class="btn btn-primary btn-rounded"><i class="fe fe-plus-circle"></i> Nueva inmobiliaria</a>
        </div>
        <?php endif ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>

<?php if (! $ready): ?>
<div class="card"><div class="card-body">
    <h5>Falta crear las tablas de inmobiliarias</h5>
    <p class="mb-0">Ejecuta <code>app/Database/sql/2026-11-agencies.sql</code> en phpMyAdmin. Crea Grupo Inmobiliario Frices y le pasa las propiedades y agentes actuales.</p>
</div></div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-center mb-0 ag-table">
                <thead>
                    <tr><th>Inmobiliaria</th><th>Colores</th><th class="text-center">Agentes</th><th class="text-center">Propiedades</th><th>Encargado</th><th>Estado</th><th class="text-right">Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($agencies as $a): $id = (int) $a['agency_id']; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="ag-logo<?= ! empty($a['logo']) ? ' has-img' : '' ?>" style="<?= ! empty($a['logo']) ? '' : 'background: ' . esc($a['color_primary'], 'attr') . '; color: ' . esc($a['color_accent'], 'attr') . ';' ?>">
                                    <?php if (! empty($a['logo'])): $ic = Agency::iconUrl($a['logo']); ?><img src="<?= esc($ic, 'attr') ?>" alt=""<?= $ic !== Agency::logoUrl($a['logo']) ? ' class="is-icon"' : '' ?>><?php elseif ($a['slug'] === 'frices'): ?><svg width="34" height="34" viewBox="0 0 100 100" aria-hidden="true"><path d="M66.5 25 L24 50 H33.5 V72 H55 V58 H66 V67 Q66 71.5 70.5 70.5 C80 68 90 59 91.8 46.3 A42 42 0 0 0 50 8 A42 42 0 0 0 8 50 A42 42 0 0 0 50 92 A42 42 0 0 0 89.3 64.7" fill="none" stroke="<?= esc($a['color_accent'], 'attr') ?>" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg><?php else: ?><?= esc(Agency::initials($a['name'])) ?><?php endif ?>
                                </span>
                                <div class="ml-3">
                                    <a href="<?= site_url('admin/agencies/edit/' . $id) ?>" class="font-weight-bold text-dark"><?= esc($a['name']) ?></a>
                                    <div class="small text-muted"><?= esc(preg_replace('#^https?://(www\.)?#', '', (string) $a['website']) ?: 'Sin sitio web') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="ag-sw" style="background: <?= esc($a['color_primary'], 'attr') ?>"></span><span class="ag-sw" style="background: <?= esc($a['color_accent'], 'attr') ?>"></span></td>
                        <td class="text-center font-weight-bold"><?= $stats[$id]['agents'] ?? 0 ?></td>
                        <td class="text-center font-weight-bold"><?= $stats[$id]['properties'] ?? 0 ?></td>
                        <td><?= ! empty($a['manager_uid']) && isset($managers[(int) $a['manager_uid']]) ? esc($managers[(int) $a['manager_uid']]) : '<span class="text-muted">Sin encargado</span>' ?></td>
                        <td><span class="badge badge-pill <?= $a['status'] === 'active' ? 'bg-success-light' : 'bg-danger-light' ?>"><?= Agency::STATUSES[$a['status']] ?? esc($a['status']) ?></span></td>
                        <td class="text-right"><a href="<?= site_url('admin/agencies/edit/' . $id) ?>" class="btn btn-sm bg-success-light"><i class="fe fe-pencil"></i> Editar</a></td>
                    </tr>
                    <?php endforeach ?>
                    <tr class="ag-indep">
                        <td colspan="7"><i class="fe fe-user"></i> <b>Agentes independientes</b> (sin inmobiliaria): <?= $stats[0]['agents'] ?? 0 ?> agentes · <?= $stats[0]['properties'] ?? 0 ?> propiedades. Sus fichas usan el logo de Real Estate by Vick.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="text-muted small">Para cambiar a un agente de inmobiliaria: Usuarios → Agentes → Editar → Inmobiliaria.</p>
<?php endif ?>

<style>
.ag-table td { vertical-align: middle; }
.ag-logo { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; flex: none; border-radius: 12px; font-weight: 800; overflow: hidden; }
.ag-logo img { width: 100%; height: 100%; object-fit: contain; background: #fff; padding: 4px; }
.ag-logo img.is-icon { object-fit: cover; background: #fff; padding: 0; border-radius: 50%; }
.ag-logo.has-img { background: #fff; border-radius: 50%; box-shadow: 0 0 0 1px rgba(15, 23, 42, .12); }
.ag-sw { display: inline-block; width: 18px; height: 18px; margin-right: 4px; border-radius: 5px; border: 1px solid rgba(0,0,0,.1); vertical-align: middle; }
.ag-indep td { background: #FAFAF8; color: #6B7280; font-size: 13px; }
</style>
<?= $this->endSection() ?>
