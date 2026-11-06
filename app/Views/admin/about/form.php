<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row"><div class="col"><h3 class="page-title"><?= esc($pageTitle) ?></h3></div></div>
</div>

<div class="row">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <?php $action = $about
                    ? site_url('admin/about/edit/' . $about['id'])
                    : site_url('admin/about/create'); ?>
                <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= esc($about['title'] ?? old('title')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contenido *</label>
                        <textarea name="content" class="form-control" rows="8" required><?= $about['content'] ?? '' ?></textarea>
                    </div>
                    <div class="form-group" style="max-width: 320px;">
                        <?= view('partials/file_upload', [
                            'name'    => 'aimage',
                            'label'   => 'Imagen',
                            'current' => ($about && ! empty($about['image'])) ? base_url('uploads/about/' . $about['image']) : '',
                            'tile'    => true,
                        ]) ?>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <?= $about ? 'Guardar Cambios' : 'Crear Sección' ?>
                    </button>
                    <a href="<?= site_url('admin/about') ?>" class="btn btn-secondary ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
