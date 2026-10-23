<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Mis Propiedades</h2></div>
            <div class="col-md-6 text-right pt-3">
                <a href="<?= site_url('my-properties/add') ?>" class="btn btn-success">+ Publicar Propiedad</a>
            </div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <?php if (empty($properties)): ?>
        <div class="text-center text-muted py-5">
            <p>Aún no has publicado ninguna propiedad.</p>
            <a href="<?= site_url('my-properties/add') ?>" class="btn btn-success">Publicar ahora</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Precio</th>
                        <th>Ciudad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $p): ?>
                    <tr>
                        <td>
                            <img src="<?= base_url('uploads/property/' . esc($p['pimage'])) ?>"
                                 alt="<?= esc($p['title']) ?>" width="70" height="50" style="object-fit:cover;">
                        </td>
                        <td class="text-capitalize"><?= esc($p['title']) ?></td>
                        <td class="text-capitalize"><?= esc($p['type']) ?></td>
                        <td><span class="badge badge-<?= $p['stype'] === 'sale' ? 'success' : 'info' ?>">
                            <?= $p['stype'] === 'sale' ? 'Venta' : 'Renta' ?>
                        </span></td>
                        <td>$<?= number_format($p['price'], 0) ?></td>
                        <td><?= esc($p['city']) ?></td>
                        <td>
                            <a href="<?= site_url('my-properties/edit/' . $p['pid']) ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="<?= site_url('my-properties/delete/' . $p['pid']) ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar esta propiedad?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
