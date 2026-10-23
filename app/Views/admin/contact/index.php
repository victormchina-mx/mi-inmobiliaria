<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Mensajes de Contacto</h3></div></div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="datatable table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Asunto</th><th>Mensaje</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $i => $c): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($c['name']) ?></td>
                                <td><?= esc($c['email']) ?></td>
                                <td><?= esc($c['phone']) ?></td>
                                <td><?= esc($c['subject']) ?></td>
                                <td><?= esc(substr($c['message'], 0, 60)) ?>...</td>
                                <td>
                                    <a href="<?= site_url('admin/contacts/delete/' . $c['cid']) ?>"
                                       class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                                        <i class="fe fe-trash-2"></i>
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
<?= $this->endSection() ?>
