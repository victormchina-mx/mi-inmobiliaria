<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('js/tinymce/init-tinymce.min.js') ?>">
<link rel="stylesheet" href="<?= base_url('admin/assets/css/select2.min.css') ?>">
<style>
.state-field-wrapper { position: relative; }
#state-input[readonly] {
    background-color: #f8f9fa;
    color: #495057;
    cursor: not-allowed;
    padding-right: 36px;
}
#state-input.is-valid {
    border-color: #28a745;
    background-image: none;
    padding-right: 36px;
}
.state-lock-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    pointer-events: none;
    font-size: 13px;
}
#state-input.is-valid ~ .state-lock-icon {
    color: #28a745;
}
</style>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipo de propiedad *</label>
                        <select name="ptype" class="form-control" required>
                            <?php foreach (['apartment'=>'Departamento','building'=>'Edificio','house'=>'Casa','villa'=>'Villa','office'=>'Oficina','local'=>'Local','bodega'=>'Bodega','terreno'=>'Terreno'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($property['type'] ?? old('ptype')) === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Para venta o renta *</label>
                        <select name="stype" class="form-control" required>
                            <option value="sale" <?= ($property['stype'] ?? '') === 'sale' ? 'selected' : '' ?>>Venta</option>
                            <option value="rent" <?= ($property['stype'] ?? '') === 'rent' ? 'selected' : '' ?>>Renta</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="status" id="status-select" class="form-control">
                            <?php foreach (['available'=>'Disponible','sold'=>'Vendida','rented'=>'Rentada','cancelled'=>'Cancelada'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($property['status'] ?? 'available') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-12" id="cancel-reason-wrap" style="<?= ($property['status'] ?? '') === 'cancelled' ? '' : 'display:none;' ?>">
                    <div class="form-group">
                        <label>Motivo de cancelación *</label>
                        <textarea name="cancel_reason" id="cancel-reason-field" class="form-control" rows="3"
                                  placeholder="Describe por qué se cancela esta propiedad..."><?= esc($property['cancel_reason'] ?? old('cancel_reason') ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Números -->
                <?php $numFields = ['bed'=>['bedroom','Recámaras'],'bath'=>['bathroom','Baños'],'balc'=>['balcony','1/2 Baño']]; ?>
                <?php foreach ($numFields as $name => [$field, $label]): ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?= $label ?></label>
                        <input type="number" name="<?= $name ?>" class="form-control" min="0"
                               value="<?= esc($property[$field] ?? old($name) ?? 0) ?>">
                    </div>
                </div>
                <?php endforeach ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tamaño (m²)</label>
                        <input type="number" name="asize" class="form-control" min="0"
                               value="<?= esc($property['size'] ?? old('asize') ?? 0) ?>">
                    </div>
                </div>

                <!-- Precio, ciudad, estado -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Precio ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                               value="<?= esc($property['price'] ?? old('price')) ?>" required>
                    </div>
                </div>
                <?php if (! empty($property['isFeatured'])): ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Destacada</label>
                        <p class="form-control-plaintext"><span class="badge badge-success">Destacada por el administrador</span></p>
                    </div>
                </div>
                <?php endif ?>
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
                        <?php
                        // Agrupar ciudades por estado para optgroup
                        $byState = [];
                        foreach ($cities as $c) {
                            $byState[$c['sname']][] = $c;
                        }
                        $currentCity = $property['city'] ?? old('city') ?? '';
                        ?>
                        <select name="city" id="city-select" class="form-control">
                            <option value=""></option>
                            <?php foreach ($byState as $stateName => $stateCities): ?>
                            <optgroup label="<?= esc($stateName) ?>">
                                <?php foreach ($stateCities as $c): ?>
                                <option value="<?= esc($c['cname']) ?>"
                                        data-state="<?= esc($c['sname']) ?>"
                                        <?= $currentCity === $c['cname'] ? 'selected' : '' ?>>
                                    <?= esc($c['cname']) ?>
                                </option>
                                <?php endforeach ?>
                            </optgroup>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Estado/Provincia</label>
                        <div class="state-field-wrapper position-relative">
                            <input type="text" name="state" id="state-input"
                                   class="form-control <?= !empty($property['state']) ? 'is-valid' : '' ?>"
                                   value="<?= esc($property['state'] ?? old('state') ?? '') ?>"
                                   readonly
                                   placeholder="Se completa al elegir ciudad">
                            <span class="state-lock-icon">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <small class="form-text text-muted">Se detecta automáticamente según la ciudad.</small>
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
                        <textarea name="feature" class="form-control tinymce" rows="4"><?= esc($property['feature'] ?? '') ?></textarea>
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
                <div class="col-6 col-md-3 mb-3">
                    <?= view('partials/file_upload', [
                        'name'    => $inputName,
                        'label'   => $label,
                        'current' => ($property && ! empty($property[$dbField])) ? base_url('uploads/property/' . $property[$dbField]) : '',
                        'tile'    => true,
                    ]) ?>
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
<script src="<?= base_url('admin/assets/js/select2.min.js') ?>"></script>
<script src="<?= base_url('js/tinymce/tinymce.min.js') ?>"></script>
<script>
tinymce.init({ selector: 'textarea.tinymce', height: 200, menubar: false,
    plugins: ['lists link paste'],
    toolbar: 'bold italic | bullist numlist | link' });

// Ciudad con buscador + autofill de Estado/Provincia.
(function ($) {
    $('#city-select').select2({
        placeholder: 'Busca o selecciona una ciudad...',
        allowClear:  true,
        width:       '100%',
        language: {
            noResults: function () { return 'No se encontraron ciudades'; },
            searching: function () { return 'Buscando...'; },
        },
    });

    $('#city-select').on('select2:select', function () {
        var stateName = $(this).find(':selected').data('state') || '';
        $('#state-input').val(stateName).addClass('is-valid');
    });

    $('#city-select').on('select2:clear', function () {
        $('#state-input').val('').removeClass('is-valid');
    });

    // Modo edición: resync si state vacío pero city ya seleccionada.
    if ($('#city-select').val() && ! $('#state-input').val()) {
        var stateName = $('#city-select').find(':selected').data('state') || '';
        $('#state-input').val(stateName).addClass('is-valid');
    }
}(jQuery));

// Muestra/oculta el motivo de cancelación según el Estado elegido.
(function () {
    var statusSelect = document.getElementById('status-select');
    var cancelWrap = document.getElementById('cancel-reason-wrap');
    var cancelField = document.getElementById('cancel-reason-field');
    if (! statusSelect || ! cancelWrap || ! cancelField) { return; }

    function syncCancelField() {
        var isCancelled = statusSelect.value === 'cancelled';
        cancelWrap.style.display = isCancelled ? '' : 'none';
        cancelField.required = isCancelled;
    }

    statusSelect.addEventListener('change', syncCancelField);
    syncCancelField();
})();
</script>
<?= $this->endSection() ?>
