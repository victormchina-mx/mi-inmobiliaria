<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col"><h3 class="page-title">Propiedades</h3></div>
        <div class="col-auto">
            <a href="<?= site_url('admin/property/create') ?>" class="btn btn-primary btn-rounded">
                <i class="fe fe-plus-circle"></i> Agregar Propiedad
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="datatable table table-hover table-center mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Imagen</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>BHK</th>
                                <th>Estado</th>
                                <th>Precio</th>
                                <th>Ciudad</th>
                                <th>Agente</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <img src="<?= base_url('uploads/property/' . esc($p['pimage'])) ?>"
                                         alt="" width="60" height="45" style="object-fit:cover; border-radius:4px;">
                                </td>
                                <td class="text-capitalize"><?= esc($p['title']) ?></td>
                                <td class="text-capitalize"><?= esc($p['type']) ?></td>
                                <td><?= esc($p['bhk']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $p['stype'] === 'sale' ? 'success' : 'info' ?>">
                                        <?= $p['stype'] === 'sale' ? 'Venta' : 'Renta' ?>
                                    </span>
                                </td>
                                <td>$<?= number_format($p['price'], 0) ?></td>
                                <td><?= esc($p['city']) ?></td>
                                <td class="text-capitalize"><?= esc($p['uname']) ?></td>
                                <td><?= date('d-m-Y', strtotime($p['created_at'])) ?></td>
                                <td>
                                    <a href="<?= site_url('admin/property/edit/' . $p['pid']) ?>" class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="fe fe-edit"></i>
                                    </a>
                                    <a href="<?= site_url('admin/property/delete/' . $p['pid']) ?>"
                                       class="btn btn-sm btn-danger text-white"
                                       title="Borrar" onclick="return confirm('¿Eliminar esta propiedad?')">
                                        <i class="fe fe-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<link rel="stylesheet" href="<?= base_url('admin/assets/plugins/datatables/dataTables.bootstrap4.min.css') ?>">
<script src="<?= base_url('admin/assets/plugins/datatables/jQuery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('admin/assets/plugins/datatables/dataTables.bootstrap4.min.js') ?>"></script>
<script>$('.datatable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' } });</script>
<script>$('[title]').tooltip();</script>
<?= $this->endSection() ?>
