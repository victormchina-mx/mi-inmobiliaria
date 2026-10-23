<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Detalle de Propiedad</h2></div>
            <div class="col-md-6">
                <nav aria-label="breadcrumb" class="float-right">
                    <ol class="breadcrumb bg-transparent m-0 p-0">
                        <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-white">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= site_url('property') ?>" class="text-white">Propiedades</a></li>
                        <li class="breadcrumb-item active text-white"><?= esc($property['title']) ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <!-- Main column -->
            <div class="col-lg-8">
                <!-- Galería de imágenes -->
                <div class="row">
                    <div class="col-md-12">
                        <div id="single-property" style="width:100%; height:500px; margin:30px auto 50px;">
                            <?php foreach (['pimage','pimage1','pimage2','pimage3','pimage4'] as $img): ?>
                            <?php if (! empty($property[$img])): ?>
                            <div class="ls-slide" data-ls="duration:7500; transition2d:5; kenburnszoom:in; kenburnsscale:1.2;">
                                <img width="1920" height="1080" src="<?= base_url('uploads/property/' . esc($property[$img])) ?>" class="ls-bg" alt="<?= esc($property['title']) ?>">
                            </div>
                            <?php endif ?>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>

                <!-- Info básica -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="bg-success d-table px-3 py-2 rounded text-white text-capitalize">
                            Para <?= $property['stype'] === 'sale' ? 'Venta' : 'Renta' ?>
                        </div>
                        <h5 class="mt-2 text-secondary text-capitalize"><?= esc($property['title']) ?></h5>
                        <span class="d-block text-capitalize"><i class="fas fa-map-marker-alt text-success"></i> <?= esc($property['location']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <div class="text-success h5 my-2 text-md-right">$<?= number_format($property['price'], 0) ?></div>
                        <div class="text-md-right">Precio</div>
                    </div>
                </div>

                <!-- Características -->
                <div class="property-details">
                    <div class="bg-gray property-quantity px-4 pt-4 w-100">
                        <ul>
                            <li><span class="text-secondary"><?= esc($property['size']) ?></span> m²</li>
                            <li><span class="text-secondary"><?= esc($property['bedroom']) ?></span> Cuartos</li>
                            <li><span class="text-secondary"><?= esc($property['bathroom']) ?></span> Baños</li>
                            <li><span class="text-secondary"><?= esc($property['balcony']) ?></span> Balcón</li>
                            <li><span class="text-secondary"><?= esc($property['hall']) ?></span> Sala</li>
                            <li><span class="text-secondary"><?= esc($property['kitchen']) ?></span> Cocina</li>
                        </ul>
                    </div>

                    <h4 class="text-secondary my-4">Descripción</h4>
                    <p><?= $property['pcontent'] ?></p>

                    <h5 class="mt-5 mb-4 text-secondary">Resumen de la Propiedad</h5>
                    <div class="table-striped font-14 pb-2">
                        <table class="w-100">
                            <tbody>
                                <tr>
                                    <td>BHK:</td><td class="text-capitalize"><?= esc($property['bhk']) ?></td>
                                    <td>Tipo:</td><td class="text-capitalize"><?= esc($property['type']) ?></td>
                                </tr>
                                <tr>
                                    <td>Piso:</td><td class="text-capitalize"><?= esc($property['floor']) ?></td>
                                    <td>Total pisos:</td><td class="text-capitalize"><?= esc($property['totalfloor']) ?></td>
                                </tr>
                                <tr>
                                    <td>Ciudad:</td><td class="text-capitalize"><?= esc($property['city']) ?></td>
                                    <td>Estado:</td><td class="text-capitalize"><?= esc($property['state']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (! empty($property['feature'])): ?>
                    <h5 class="mt-5 mb-4 text-secondary">Características</h5>
                    <div class="row"><?= $property['feature'] ?></div>
                    <?php endif ?>

                    <!-- Planos -->
                    <h5 class="mt-5 mb-4 text-secondary">Planos</h5>
                    <div class="accordion" id="accordionPlanos">
                        <?php if (! empty($property['mapimage'])): ?>
                        <button class="bg-gray py-3 px-4 mb-1 w-100 text-left rounded" type="button" data-toggle="collapse" data-target="#plano1">Plano Principal</button>
                        <div id="plano1" class="collapse show p-4">
                            <img src="<?= base_url('uploads/property/' . esc($property['mapimage'])) ?>" alt="Plano" class="img-fluid">
                        </div>
                        <?php endif ?>
                        <?php if (! empty($property['topmapimage'])): ?>
                        <button class="bg-gray py-3 px-4 mb-1 w-100 text-left rounded collapsed" type="button" data-toggle="collapse" data-target="#plano2">Planta Alta</button>
                        <div id="plano2" class="collapse p-4">
                            <img src="<?= base_url('uploads/property/' . esc($property['topmapimage'])) ?>" alt="Planta alta" class="img-fluid">
                        </div>
                        <?php endif ?>
                        <?php if (! empty($property['groundmapimage'])): ?>
                        <button class="bg-gray py-3 px-4 mb-1 w-100 text-left rounded collapsed" type="button" data-toggle="collapse" data-target="#plano3">Planta Baja</button>
                        <div id="plano3" class="collapse p-4">
                            <img src="<?= base_url('uploads/property/' . esc($property['groundmapimage'])) ?>" alt="Planta baja" class="img-fluid">
                        </div>
                        <?php endif ?>
                    </div>

                    <!-- Contacto con el agente -->
                    <h5 class="mt-5 mb-4 text-secondary double-down-line-left position-relative">Contactar Agente</h5>
                    <div class="agent-contact">
                        <div class="row">
                            <div class="col-sm-4 col-lg-3">
                                <img src="<?= base_url('uploads/user/' . esc($property['uimage'])) ?>" alt="<?= esc($property['uname']) ?>" height="200" width="170">
                            </div>
                            <div class="col-sm-8 col-lg-9">
                                <div class="agent-data mt-sm-20">
                                    <h6 class="text-success text-capitalize"><?= esc($property['uname']) ?></h6>
                                    <ul class="mb-3">
                                        <li><?= esc($property['uphone']) ?></li>
                                        <li><?= esc($property['uemail']) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <h4 class="double-down-line-left text-secondary position-relative pb-4 my-4">Calculadora de Cuotas</h4>
                <form class="d-inline-block w-100" action="<?= site_url('calculator') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><div class="input-group-text">$</div></div>
                        <input type="number" class="form-control" name="amount" placeholder="Precio de la propiedad" value="<?= esc($property['price']) ?>">
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><div class="input-group-text"><i class="far fa-calendar-alt"></i></div></div>
                        <input type="number" class="form-control" name="month" placeholder="Plazo en meses">
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><div class="input-group-text">%</div></div>
                        <input type="number" step="0.01" class="form-control" name="interest" placeholder="Tasa de interés">
                    </div>
                    <button type="submit" class="btn btn-danger mt-4">Calcular Cuota</button>
                </form>

                <?php if (! empty($featured)): ?>
                <h4 class="double-down-line-left text-secondary position-relative pb-4 mb-4 mt-5">Propiedades Destacadas</h4>
                <ul class="property_list_widget">
                    <?php foreach ($featured as $fp): ?>
                    <li>
                        <img src="<?= base_url('uploads/property/' . esc($fp['pimage'])) ?>" alt="<?= esc($fp['title']) ?>">
                        <h6 class="text-secondary hover-text-success text-capitalize">
                            <a href="<?= site_url('property/' . $fp['pid']) ?>"><?= esc($fp['title']) ?></a>
                        </h6>
                        <span class="font-14"><i class="fas fa-map-marker-alt text-success"></i> <?= esc($fp['location']) ?></span>
                    </li>
                    <?php endforeach ?>
                </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
