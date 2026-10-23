<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row">
        <div class="col"><h3 class="page-title"><?= esc($title) ?></h3></div>
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
                                <th>Foto</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $i => $u): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <img src="<?= base_url('uploads/user/' . esc($u['uimage'])) ?>"
                                         alt="<?= esc($u['uname']) ?>" width="40" height="40"
                                         style="object-fit:cover; border-radius:50%;">
                                </td>
                                <td><?= esc($u['uname']) ?></td>
                                <td><?= esc($u['uemail']) ?></td>
                                <td><?= esc($u['uphone']) ?></td>
                                <td class="text-capitalize"><?= esc($u['utype']) ?></td>
                                <td>
                                    <?php
                                    [$editUrl, $deleteUrl] = match($type) {
                                        'agent'   => [
                                            site_url('admin/agents/edit/'   . $u['uid']),
                                            site_url('admin/agents/delete/' . $u['uid']),
                                        ],
                                        'builder' => [
                                            site_url('admin/builders/edit/'   . $u['uid']),
                                            site_url('admin/builders/delete/' . $u['uid']),
                                        ],
                                        default   => [
                                            site_url('admin/users/edit/'   . $u['uid']),
                                            site_url('admin/users/delete/' . $u['uid']),
                                        ],
                                    };
                                    ?>
                                    <a href="<?= $editUrl ?>" class="btn btn-sm btn-primary mr-1"
                                       title="Editar">
                                        <i class="fe fe-edit"></i>
                                    </a>
                                    <a href="<?= $deleteUrl ?>" class="btn btn-sm btn-danger text-white"
                                       title="Borrar" onclick="return confirm('¿Eliminar este usuario?')">
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
