<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('js/tinymce/init-tinymce.min.js') ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h2 class="page-name float-left text-white text-uppercase">
                    <?= $property ? 'Editar Propiedad' : 'Publicar Propiedad' ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <?php $action = $property
            ? site_url('my-properties/edit/' . $property['pid'])
            : site_url('my-properties/add'); ?>
        <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row">
                <!-- Título -->
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= esc($property['title'] ?? old('title')) ?>" required>
                    </div>
                </div>

                <!-- Tipo y stype -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tipo de propiedad *</label>
                        <select name="ptype" class="form-control" required>
                            <?php foreach (['apartment'=>'Departamento','flat'=>'Flat','building'=>'Edificio','house'=>'Casa','villa'=>'Villa','office'=>'Oficina'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($property['type'] ?? old('ptype')) === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Para venta o renta *</label>
                        <select name="stype" class="form-control" required>
                            <option value="sale" <?= ($property['stype'] ?? '') === 'sale' ? 'selected' : '' ?>>Venta</option>
                            <option value="rent" <?= ($property['stype'] ?? '') === 'rent' ? 'selected' : '' ?>>Renta</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>BHK</label>
                        <select name="bhk" class="form-control">
                            <?php foreach (['1 BHK','2 BHK','3 BHK','4 BHK','5+ BHK'] as $b): ?>
                            <option value="<?= $b ?>" <?= ($property['bhk'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="status" class="form-control">
                            <?php foreach (['available'=>'Disponible','sold'=>'Vendida','rented'=>'Rentada'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($property['status'] ?? 'available') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <!-- Números -->
                <?php $numFields = ['bed'=>['bedroom','Cuartos'],'bath'=>['bathroom','Baños'],'balc'=>['balcony','Balcones'],'kitc'=>['kitchen','Cocina'],'hall'=>['hall','Sala']]; ?>
                <?php foreach ($numFields as $name => [$field, $label]): ?>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?= $label ?></label>
                        <input type="number" name="<?= $name ?>" class="form-control" min="0"
                               value="<?= esc($property[$field] ?? old($name) ?? 0) ?>">
                    </div>
                </div>
                <?php endforeach ?>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tamaño (m²)</label>
                        <input type="number" name="asize" class="form-control" min="0"
                               value="<?= esc($property['size'] ?? old('asize') ?? 0) ?>">
                    </div>
                </div>

                <!-- Precio, piso, ciudad, estado -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Precio ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                               value="<?= esc($property['price'] ?? old('price')) ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Piso</label>
                        <input type="text" name="floor" class="form-control"
                               value="<?= esc($property['floor'] ?? old('floor')) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Total de pisos</label>
                        <input type="text" name="totalfl" class="form-control"
                               value="<?= esc($property['totalfloor'] ?? old('totalfl')) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>¿Destacada?</label>
                        <select name="isFeatured" class="form-control">
                            <option value="0" <?= ($property['isFeatured'] ?? 0) == 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ($property['isFeatured'] ?? 0) == 1 ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ubicación</label>
                        <input type="text" name="loc" class="form-control"
                               value="<?= esc($property['location'] ?? old('loc')) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" name="city" class="form-control"
                               value="<?= esc($property['city'] ?? old('city')) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Estado/Provincia</label>
                        <input type="text" name="state" class="form-control"
                               value="<?= esc($property['state'] ?? old('state')) ?>">
                    </div>
                </div>

                <!-- Descripción y características -->
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="content" class="form-control tinymce" rows="6"><?= esc($property['pcontent'] ?? old('content')) ?></textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Características (HTML)</label>
                        <textarea name="feature" class="form-control tinymce" rows="4"><?= $property['feature'] ?? '' ?></textarea>
                    </div>
                </div>

                <!-- Imágenes -->
                <div class="col-md-12"><h5 class="text-secondary mt-3 mb-2">Imágenes de la Propiedad</h5></div>
                <?php
                $imgFields = [
                    'aimage'  => ['pimage',  'Imagen Principal'],
                    'aimage1' => ['pimage1', 'Imagen 2'],
                    'aimage2' => ['pimage2', 'Imagen 3'],
                    'aimage3' => ['pimage3', 'Imagen 4'],
                    'aimage4' => ['pimage4', 'Imagen 5'],
                    'fimage'  => ['mapimage',       'Plano Principal'],
                    'fimage1' => ['topmapimage',    'Planta Alta'],
                    'fimage2' => ['groundmapimage', 'Planta Baja'],
                ];
                ?>
                <?php foreach ($imgFields as $inputName => [$dbField, $label]): ?>
                <div class="col-md-3 mb-3">
                    <label><?= $label ?></label>
                    <?php if ($property && ! empty($property[$dbField])): ?>
                    <div class="mb-1">
                        <img src="<?= base_url('uploads/property/' . esc($property[$dbField])) ?>"
                             alt="<?= $label ?>" height="60" style="object-fit:cover;">
                    </div>
                    <?php endif ?>
                    <input type="file" name="<?= $inputName ?>" class="form-control-file" accept="image/*">
                </div>
                <?php endforeach ?>

                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-success px-5">
                        <?= $property ? 'Guardar Cambios' : 'Publicar Propiedad' ?>
                    </button>
                    <a href="<?= site_url('my-properties') ?>" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/tinymce/tinymce.min.js') ?>"></script>
<script>
tinymce.init({ selector: 'textarea.tinymce', height: 200, menubar: false,
    plugins: ['lists link paste'],
    toolbar: 'bold italic | bullist numlist | link' });
</script>
<?= $this->endSection() ?>
