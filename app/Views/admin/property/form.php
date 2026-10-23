<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row">
        <div class="col"><h3 class="page-title"><?= esc($pageTitle) ?></h3></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php $action = $property
                    ? site_url('admin/property/edit/' . $property['pid'])
                    : site_url('admin/property/create'); ?>
                <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Título *</label>
                                <input type="text" name="title" class="form-control"
                                       value="<?= esc($property['title'] ?? old('title')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tipo *</label>
                                <select name="ptype" class="form-control" required>
                                    <?php foreach (['apartment'=>'Departamento','flat'=>'Flat','building'=>'Edificio','house'=>'Casa','villa'=>'Villa','office'=>'Oficina'] as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= ($property['type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Para *</label>
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
                                <label>Precio ($) *</label>
                                <input type="number" step="0.01" name="price" class="form-control"
                                       value="<?= esc($property['price'] ?? old('price')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tamaño (m²)</label>
                                <input type="number" name="asize" class="form-control"
                                       value="<?= esc($property['size'] ?? 0) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Estado de publicación</label>
                                <select name="status" class="form-control">
                                    <?php foreach (['available'=>'Disponible','sold'=>'Vendida','rented'=>'Rentada'] as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= ($property['status'] ?? 'available') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <?php foreach (['bed'=>['bedroom','Cuartos'],'bath'=>['bathroom','Baños'],'balc'=>['balcony','Balcones'],'kitc'=>['kitchen','Cocina'],'hall'=>['hall','Sala']] as $name => [$field, $label]): ?>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label><?= $label ?></label>
                                <input type="number" name="<?= $name ?>" class="form-control" min="0"
                                       value="<?= esc($property[$field] ?? 0) ?>">
                            </div>
                        </div>
                        <?php endforeach ?>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Piso</label>
                                <input type="text" name="floor" class="form-control"
                                       value="<?= esc($property['floor'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Total pisos</label>
                                <input type="text" name="totalfl" class="form-control"
                                       value="<?= esc($property['totalfloor'] ?? '') ?>">
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Agente/Propietario</label>
                                <select name="uid" class="form-control">
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['uid'] ?>" <?= ($property['uid'] ?? 0) == $u['uid'] ? 'selected' : '' ?>>
                                        <?= esc($u['uname']) ?> (<?= esc($u['utype']) ?>)
                                    </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ubicación</label>
                                <input type="text" name="loc" class="form-control" value="<?= esc($property['location'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" name="city" class="form-control" value="<?= esc($property['city'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado/Provincia</label>
                                <input type="text" name="state" class="form-control" value="<?= esc($property['state'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="content" class="form-control" rows="5"><?= esc($property['pcontent'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Características</label>
                                <textarea name="feature" class="form-control" rows="3"><?= $property['feature'] ?? '' ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-12"><h5 class="text-secondary mt-2 mb-3">Imágenes</h5></div>
                        <?php $imgDefs = [
                            'aimage'=>['pimage','Principal'],'aimage1'=>['pimage1','Img 2'],'aimage2'=>['pimage2','Img 3'],
                            'aimage3'=>['pimage3','Img 4'],'aimage4'=>['pimage4','Img 5'],
                            'fimage'=>['mapimage','Plano'],'fimage1'=>['topmapimage','Planta Alta'],'fimage2'=>['groundmapimage','Planta Baja'],
                        ]; ?>
                        <?php foreach ($imgDefs as $input => [$field, $label]): ?>
                        <div class="col-md-3 mb-3">
                            <label><?= $label ?></label>
                            <?php if ($property && ! empty($property[$field])): ?>
                            <div class="mb-1">
                                <img src="<?= base_url('uploads/property/' . esc($property[$field])) ?>"
                                     height="50" style="object-fit:cover; border-radius:4px;">
                            </div>
                            <?php endif ?>
                            <input type="file" name="<?= $input ?>" class="form-control-file" accept="image/*">
                        </div>
                        <?php endforeach ?>

                        <div class="col-md-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <?= $property ? 'Guardar Cambios' : 'Crear Propiedad' ?>
                            </button>
                            <a href="<?= site_url('admin/property') ?>" class="btn btn-secondary ml-2">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
