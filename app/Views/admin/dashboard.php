<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col"><h3 class="page-title">Dashboard</h3></div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row">
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-primary"><i class="fe fe-users"></i></span>
                    <div class="dash-count">
                        <div class="dash-title">Usuarios</div>
                        <div class="dash-counts"><p><?= $totalUsers ?></p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-warning"><i class="fe fe-users"></i></span>
                    <div class="dash-count">
                        <div class="dash-title">Agentes</div>
                        <div class="dash-counts"><p><?= $totalAgents ?></p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-success"><i class="fe fe-home"></i></span>
                    <div class="dash-count">
                        <div class="dash-title">Total Propiedades</div>
                        <div class="dash-counts"><p><?= $totalProps ?></p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-danger"><i class="fe fe-home"></i></span>
                    <div class="dash-count">
                        <div class="dash-title">Constructores</div>
                        <div class="dash-counts"><p><?= $totalBuilders ?></p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Propiedades por tipo -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Propiedades por tipo</h4>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3"><div class="bg-light p-3 rounded"><h5><?= $apartments ?></h5><small>Departamentos</small></div></div>
                    <div class="col-md-3"><div class="bg-light p-3 rounded"><h5><?= $houses ?></h5><small>Casas</small></div></div>
                    <div class="col-md-3"><div class="bg-light p-3 rounded"><h5><?= $buildings ?></h5><small>Edificios</small></div></div>
                    <div class="col-md-3"><div class="bg-light p-3 rounded"><h5><?= $flats ?></h5><small>Flats</small></div></div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-md-6"><div class="bg-success text-white p-3 rounded"><h5><?= $saleProps ?></h5><small>En Venta</small></div></div>
                    <div class="col-md-6"><div class="bg-info text-white p-3 rounded"><h5><?= $rentProps ?></h5><small>En Renta</small></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
