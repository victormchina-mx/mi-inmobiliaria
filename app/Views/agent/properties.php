<?= $this->extend('layouts/main') ?>

<?php
/**
 * "Todas las propiedades" (Mi cuenta, solo agentes y constructores):
 * propiedades de todos los agentes con Agendar cita (agente/agenda) y Ficha técnica.
 * Controlador: AgentArea::properties · Estilos: .ap-* en style.css
 */
use App\Libraries\FichaTecnica;
use App\Libraries\Labels;
use App\Libraries\PropertyExtras;

$statusLabels = ['available' => 'Disponible', 'sold' => 'Vendida', 'rented' => 'Rentada', 'cancelled' => 'Cancelada'];
$hasFilters   = array_filter(array_diff_key($filters, ['status' => 1])) || $filters['status'] !== 'available';
?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-8"><h1 class="h2 page-name float-left text-white text-uppercase">Todas las propiedades</h1></div>
        </div>
    </div>
</div>

<div class="full-row ap-page">
    <div class="container">
        <div class="ap-head">
            <div>
                <span class="sec-eyebrow">Área de agentes</span>
                <h2 class="sec-title">Propiedades de todos los agentes</h2>
                <p class="ap-sub">Genera la ficha técnica de cualquier propiedad para compartirla con tus clientes.</p>
            </div>
            <nav class="ap-tabs" aria-label="Área de agentes">
                <span class="ap-tab is-active" aria-current="page"><i class="ms" aria-hidden="true">location_on</i>Propiedades</span>
                <a class="ap-tab" href="<?= site_url('agente/agenda') ?>"><i class="ms" aria-hidden="true">calendar_month</i>Agenda de citas</a>
            </nav>
        </div>

        <form class="ap-filters" method="get" action="<?= site_url('agente/propiedades') ?>">
            <label class="ap-search">
                <i class="ms" aria-hidden="true">search</i>
                <input type="search" name="q" value="<?= esc($filters['q']) ?>" placeholder="Código (VMCH-0025), colonia o título…" aria-label="Buscar">
            </label>
            <select name="stype" aria-label="Operación">
                <option value="">Venta y renta</option>
                <option value="sale"<?= $filters['stype'] === 'sale' ? ' selected' : '' ?>>Venta</option>
                <option value="rent"<?= $filters['stype'] === 'rent' ? ' selected' : '' ?>>Renta</option>
            </select>
            <select name="type" aria-label="Tipo">
                <option value="">Todos los tipos</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= esc($t, 'attr') ?>"<?= $filters['type'] === $t ? ' selected' : '' ?>><?= esc(Labels::propertyType($t)) ?></option>
                <?php endforeach ?>
            </select>
            <select name="city" aria-label="Zona">
                <option value="">Todas las zonas</option>
                <?php foreach ($cities as $c): ?>
                <option value="<?= esc($c, 'attr') ?>"<?= $filters['city'] === $c ? ' selected' : '' ?>><?= esc($c) ?></option>
                <?php endforeach ?>
            </select>
            <select name="agent" aria-label="Agente">
                <option value="">Todos los agentes</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= (int) $a['uid'] ?>"<?= $filters['agent'] === (string) $a['uid'] ? ' selected' : '' ?>><?= esc($a['uname']) ?></option>
                <?php endforeach ?>
            </select>
            <?php if ($keysReady): ?>
            <select name="keys" aria-label="Llaves">
                <option value="">Llaves: todas</option>
                <?php foreach (PropertyExtras::KEYS as $k => $label): ?>
                <option value="<?= $k ?>"<?= $filters['keys'] === $k ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
            <?php endif ?>
            <select name="status" aria-label="Estado">
                <?php foreach (\App\Controllers\AgentArea::STATUSES as $k => $label): ?>
                <option value="<?= $k ?>"<?= $filters['status'] === $k ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
            <button type="submit" class="ap-btn ap-btn--dark"><i class="ms" aria-hidden="true">search</i>Buscar</button>
            <?php if ($hasFilters): ?>
            <a class="ap-clear" href="<?= site_url('agente/propiedades') ?>">Quitar filtros</a>
            <?php endif ?>
        </form>

        <p class="ap-count"><?= count($properties) ?> <?= count($properties) === 1 ? 'propiedad' : 'propiedades' ?></p>

        <?php if (! $properties): ?>
        <div class="ap-empty">
            <i class="ms" aria-hidden="true">search</i>
            <p>No hay propiedades con esos filtros.</p>
            <a class="ap-btn ap-btn--dark" href="<?= site_url('agente/propiedades') ?>">Ver todas</a>
        </div>
        <?php else: ?>
        <div class="ap-grid">
            <?php foreach ($properties as $p): ?>
            <?php
            $isRent = $p['stype'] === 'rent';
            $zone   = implode(', ', array_filter([trim((string) $p['location']), trim((string) $p['city'])]));
            $keys   = $p['keys_status'] ?? null;
            ?>
            <article class="ap-card">
                <a class="ap-card__img" href="<?= site_url('property/' . $p['pid']) ?>" target="_blank" rel="noopener">
                    <?php if (! empty($p['pimage'])): ?>
                    <img src="<?= esc(thumb_url('property', $p['pimage'], 400)) ?>" srcset="<?= esc(thumb_srcset('property', $p['pimage'], [400, 800])) ?>" sizes="(min-width: 992px) 360px, 100vw" alt="<?= esc($p['title']) ?>" loading="lazy" decoding="async" width="400" height="225">
                    <?php endif ?>
                    <span class="ap-chip"><?= FichaTecnica::code((int) $p['pid']) ?></span>
                    <span class="ap-chip ap-chip--op<?= $isRent ? ' is-rent' : '' ?>"><?= $isRent ? 'RENTA' : 'VENTA' ?></span>
                    <?php if ($p['status'] !== 'available'): ?>
                    <span class="ap-chip ap-chip--status"><?= $statusLabels[$p['status']] ?? esc($p['status']) ?></span>
                    <?php endif ?>
                </a>
                <div class="ap-card__body">
                    <div class="ap-price">$<?= number_format((float) $p['price'], 0) ?><?php if ($isRent): ?> <small>/mes</small><?php endif ?></div>
                    <h3 class="ap-title"><?= esc(mb_strtoupper(mb_substr((string) $p['title'], 0, 1)) . mb_substr((string) $p['title'], 1)) ?></h3>
                    <p class="ap-zone text-capitalize"><i class="ms" aria-hidden="true">location_on</i><?= esc($zone ?: 'Sin ubicación') ?></p>
                    <div class="ap-meta">
                        <span class="ap-agent">
                            <?php if (! empty($p['uimage'])): ?>
                            <img src="<?= esc(thumb_url('user', $p['uimage'], 200)) ?>" alt="" width="30" height="30" loading="lazy">
                            <?php else: ?>
                            <span class="ap-agent__ini" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $p['uname'], 0, 1))) ?></span>
                            <?php endif ?>
                            <span class="text-truncate"><?= esc($p['uname']) ?></span>
                        </span>
                        <?php if ($keysReady): ?>
                        <span class="ap-keys<?= in_array($keys, ['owner', 'occupied', 'other'], true) ? ' is-warn' : ($keys ? '' : ' is-none') ?>" title="<?= esc($p['keys_note'] ?? '', 'attr') ?>">
                            <i class="ms" aria-hidden="true">key</i><?= esc(PropertyExtras::keysLabel($keys)) ?>
                        </span>
                        <?php endif ?>
                    </div>
                    <div class="ap-actions">
                        <a class="ap-btn ap-btn--dark" href="<?= site_url('agente/agenda?pid=' . $p['pid']) ?>#ag-panel">
                            <i class="ms" aria-hidden="true">event_available</i>Agendar cita
                        </a>
                        <a class="ap-btn ap-btn--gold" href="<?= site_url('agente/ficha/' . $p['pid']) ?>">
                            <i class="ms" aria-hidden="true">description</i>Ficha técnica
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach ?>
        </div>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
