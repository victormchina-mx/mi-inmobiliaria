<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Calculadora de Hipoteca</h2></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="shadow p-4 rounded">
                    <h4 class="text-secondary mb-4">Calcular cuota mensual</h4>
                    <form method="post" action="<?= site_url('calculator') ?>">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label>Monto del préstamo ($)</label>
                            <input type="number" name="amount" class="form-control" placeholder="Ej: 150000"
                                   value="<?= esc($amount ?? '') ?>" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Plazo (meses)</label>
                            <input type="number" name="month" class="form-control" placeholder="Ej: 120"
                                   value="<?= esc($months ?? '') ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Tasa de interés anual (%)</label>
                            <input type="number" step="0.01" name="interest" class="form-control" placeholder="Ej: 7.5"
                                   value="<?= esc($interest ?? '') ?>" min="0" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Calcular</button>
                    </form>

                    <?php if ($result !== null): ?>
                    <div class="mt-4 p-3 bg-success text-white rounded text-center">
                        <h5>Cuota mensual estimada</h5>
                        <h2>$<?= number_format($result, 2) ?></h2>
                        <small>Basado en $<?= number_format($amount, 0) ?> a <?= esc($months) ?> meses al <?= esc($interest) ?>% anual</small>
                    </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
