<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Banner -->
<div class="overlay-black w-100 slider-banner1 position-relative"
     style="background-image: url('<?= base_url('images/banner/rshmpg.jpg') ?>'); background-size: cover; background-position: center center;">
    <div class="container h-100">
        <div class="row h-100 align-items-center">
            <div class="col-lg-12">
                <div class="text-white">
                    <h1 class="mb-4"><span class="text-success">Déjanos</span><br>Guiarte a tu Hogar</h1>
                    <form method="post" action="<?= site_url('property/grid') ?>">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    <select class="form-control" name="type">
                                        <option value="">Tipo de Propiedad</option>
                                        <option value="apartment">Departamento</option>
                                        <option value="flat">Flat</option>
                                        <option value="building">Edificio</option>
                                        <option value="house">Casa</option>
                                        <option value="villa">Villa</option>
                                        <option value="office">Oficina</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group">
                                    <select class="form-control" name="stype">
                                        <option value="">Estado</option>
                                        <option value="rent">Renta</option>
                                        <option value="sale">Venta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8 col-lg-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="city" placeholder="Ciudad...">
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <button type="submit" class="btn btn-success w-100">Buscar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Servicios -->
<div class="full-row bg-gray">
    <div class="container">
        <div class="row"><div class="col-lg-12"><h2 class="text-secondary double-down-line text-center mb-5">Lo que Hacemos</h2></div></div>
        <div class="text-box-one">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="p-4 text-center hover-bg-white hover-shadow rounded mb-4 transation-3s">
                        <i class="flaticon-rent text-success flat-medium"></i>
                        <h5 class="text-secondary py-3 m-0">Servicio de Venta</h5>
                        <p>Te ayudamos a vender tu propiedad al mejor precio del mercado.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="p-4 text-center hover-bg-white hover-shadow rounded mb-4 transation-3s">
                        <i class="flaticon-for-rent text-success flat-medium"></i>
                        <h5 class="text-secondary py-3 m-0">Servicio de Renta</h5>
                        <p>Encuentra o publica propiedades en renta con facilidad.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="p-4 text-center hover-bg-white hover-shadow rounded mb-4 transation-3s">
                        <i class="flaticon-list text-success flat-medium"></i>
                        <h5 class="text-secondary py-3 m-0">Listado de Propiedades</h5>
                        <p>Catálogo completo de propiedades disponibles.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="p-4 text-center hover-bg-white hover-shadow rounded mb-4 transation-3s">
                        <i class="flaticon-diagram text-success flat-medium"></i>
                        <h5 class="text-secondary py-3 m-0">Inversión Legal</h5>
                        <p>Asesoría legal y financiera para tu inversión.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Propiedades recientes -->
<div class="full-row">
    <div class="container">
        <div class="row">
            <div class="col-md-12"><h2 class="text-secondary double-down-line text-center mb-4">Propiedades Recientes</h2></div>
            <div class="col-md-12">
                <div class="row">
                    <?php foreach ($properties as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="featured-thumb hover-zoomer mb-4">
                            <div class="overlay-black overflow-hidden position-relative">
                                <img src="<?= base_url('uploads/property/' . esc($p['pimage'])) ?>" alt="<?= esc($p['title']) ?>">
                                <div class="featured bg-success text-white">Nuevo</div>
                                <div class="sale bg-success text-white text-capitalize">Para <?= esc($p['stype'] === 'sale' ? 'Venta' : 'Renta') ?></div>
                                <div class="price text-primary"><b>$<?= number_format($p['price'], 0) ?></b> <span class="text-white"><?= esc($p['size']) ?> m²</span></div>
                            </div>
                            <div class="featured-thumb-data shadow-one">
                                <div class="p-3">
                                    <h5 class="text-secondary hover-text-success mb-2 text-capitalize">
                                        <a href="<?= site_url('property/' . $p['pid']) ?>"><?= esc($p['title']) ?></a>
                                    </h5>
                                    <span class="location text-capitalize"><i class="fas fa-map-marker-alt text-success"></i> <?= esc($p['location']) ?></span>
                                </div>
                                <div class="bg-gray quantity px-4 pt-4">
                                    <ul>
                                        <li><span><?= esc($p['size']) ?></span> m²</li>
                                        <li><span><?= esc($p['bedroom']) ?></span> Cuartos</li>
                                        <li><span><?= esc($p['bathroom']) ?></span> Baños</li>
                                        <li><span><?= esc($p['kitchen']) ?></span> Cocina</li>
                                        <li><span><?= esc($p['balcony']) ?></span> Balcón</li>
                                    </ul>
                                </div>
                                <div class="p-4 d-inline-block w-100">
                                    <div class="float-left text-capitalize"><i class="fas fa-user text-success mr-1"></i>Por: <?= esc($p['uname']) ?></div>
                                    <div class="float-right"><i class="far fa-calendar-alt text-success mr-1"></i> <?= date('d-m-Y', strtotime($p['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                    <?php if (empty($properties)): ?>
                    <div class="col-12 text-center text-muted py-5">No hay propiedades disponibles.</div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Por qué elegirnos -->
<div class="full-row living bg-one overlay-secondary-half"
     style="background-image: url('<?= base_url('images/01.jpg') ?>'); background-size: cover;">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-6">
                <div class="living-list pr-4">
                    <h3 class="pb-4 mb-3 text-white">Por Qué Elegirnos</h3>
                    <ul>
                        <li class="mb-4 text-white d-flex">
                            <i class="flaticon-reward flat-medium float-left d-table mr-4 text-success"></i>
                            <div class="pl-2"><h5 class="mb-3">Los Mejor Calificados</h5><p>Años de experiencia avalados por cientos de clientes satisfechos.</p></div>
                        </li>
                        <li class="mb-4 text-white d-flex">
                            <i class="flaticon-real-estate flat-medium float-left d-table mr-4 text-success"></i>
                            <div class="pl-2"><h5 class="mb-3">Calidad de Servicio</h5><p>Atención personalizada en cada etapa de tu proceso.</p></div>
                        </li>
                        <li class="mb-4 text-white d-flex">
                            <i class="flaticon-seller flat-medium float-left d-table mr-4 text-success"></i>
                            <div class="pl-2"><h5 class="mb-3">Agentes Experimentados</h5><p>Nuestros agentes conocen el mercado y trabajan para ti.</p></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="full-row overlay-secondary"
     style="background-image: url('<?= base_url('images/breadcromb.jpg') ?>'); background-size: cover;">
    <div class="container">
        <div class="fact-counter">
            <div class="row">
                <div class="col-md-3">
                    <div class="count wow text-center mb-sm-50">
                        <i class="flaticon-house flat-large text-white"></i>
                        <div class="count-num text-success my-4" data-speed="3000" data-stop="<?= $totalProps ?>"><?= $totalProps ?></div>
                        <div class="text-white h5">Propiedades Disponibles</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="count wow text-center mb-sm-50">
                        <i class="flaticon-house flat-large text-white"></i>
                        <div class="count-num text-success my-4" data-speed="3000" data-stop="<?= $saleProps ?>"><?= $saleProps ?></div>
                        <div class="text-white h5">En Venta</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="count wow text-center mb-sm-50">
                        <i class="flaticon-house flat-large text-white"></i>
                        <div class="count-num text-success my-4" data-speed="3000" data-stop="<?= $rentProps ?>"><?= $rentProps ?></div>
                        <div class="text-white h5">En Renta</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="count wow text-center mb-sm-50">
                        <i class="flaticon-man flat-large text-white"></i>
                        <div class="count-num text-success my-4" data-speed="3000" data-stop="<?= $totalUsers ?>"><?= $totalUsers ?></div>
                        <div class="text-white h5">Usuarios Registrados</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonios -->
<div class="full-row">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="content-sidebar p-4">
                    <div class="mb-3 col-lg-12">
                        <h4 class="double-down-line-left text-secondary position-relative pb-4 mb-4">Testimonios</h4>
                        <div class="recent-review owl-carousel owl-dots-gray owl-dots-hover-success">
                            <?php foreach ($feedback as $fb): ?>
                            <div class="item">
                                <div class="p-4 bg-success down-angle-white position-relative">
                                    <p class="text-white"><i class="fas fa-quote-left mr-2"></i><?= esc($fb['fdescription']) ?><i class="fas fa-quote-right ml-2"></i></p>
                                </div>
                                <div class="p-2 mt-4">
                                    <span class="text-success d-table text-capitalize"><?= esc($fb['uname']) ?></span>
                                </div>
                            </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
