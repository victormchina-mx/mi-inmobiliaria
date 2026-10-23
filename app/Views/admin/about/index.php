<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col"><h3 class="page-title">Secciones "Nosotros"</h3></div>
        <div class="col-auto">
            <a href="<?= site_url('admin/about/create') ?>" class="btn btn-primary btn-rounded">
                <i class="fe fe-plus-circle"></i> Agregar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Imagen</th><th>Título</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($abouts as $i => $a): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if (! empty($a['image'])): ?>
                                <img src="<?= base_url('uploads/about/' . esc($a['image'])) ?>"
                                     height="50" style="object-fit:cover; border-radius:4px;">
                                <?php endif ?>
                            </td>
                            <td><?= esc($a['title']) ?></td>
                            <td>
                                <a href="<?= site_url('admin/about/edit/' . $a['id']) ?>" class="btn btn-sm btn-warning"><i class="fe fe-edit"></i></a>
                                <a href="<?= site_url('admin/about/delete/' . $a['id']) ?>"
                                   class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')"><i class="fe fe-trash-2"></i></a>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
