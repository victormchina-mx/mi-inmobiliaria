<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title">Testimonios</h3></div></div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="datatable table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Usuario</th><th>Email</th><th>Testimonio</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $i => $f): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($f['uname']) ?></td>
                                <td><?= esc($f['uemail']) ?></td>
                                <td><?= esc(substr($f['fdescription'], 0, 80)) ?>...</td>
                                <td>
                                    <span class="badge badge-<?= $f['status'] ? 'success' : 'warning' ?>">
                                        <?= $f['status'] ? 'Aprobado' : 'Pendiente' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?= site_url('admin/feedback/edit/' . $f['fid']) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="<?= $f['status'] ? 0 : 1 ?>">
                                        <button type="submit" class="btn btn-sm btn-<?= $f['status'] ? 'warning' : 'success' ?>">
                                            <?= $f['status'] ? 'Desaprobar' : 'Aprobar' ?>
                                        </button>
                                    </form>
                                    <a href="<?= site_url('admin/feedback/delete/' . $f['fid']) ?>"
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
