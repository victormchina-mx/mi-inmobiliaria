<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.jpg') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-6"><h2 class="page-name float-left text-white text-uppercase">Contacto</h2></div>
        </div>
    </div>
</div>

<div class="full-row">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h3 class="text-secondary mb-4 text-center">Envíanos un mensaje</h3>
                <form method="post" action="<?= site_url('contact') ?>">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" name="name" class="form-control" placeholder="Nombre *"
                                       value="<?= esc(old('name')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder="Correo electrónico *"
                                       value="<?= esc(old('email')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" name="phone" class="form-control" placeholder="Teléfono"
                                       value="<?= esc(old('phone')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" name="subject" class="form-control" placeholder="Asunto *"
                                       value="<?= esc(old('subject')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <textarea name="message" class="form-control" rows="5" placeholder="Mensaje *" required><?= esc(old('message')) ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-success px-5">Enviar Mensaje</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
