<?= $this->extend('layouts/main') ?>

<?php
/**
 * Agenda de citas compartida (Mi cuenta, solo agentes y constructores).
 * Semana en calendario (en celular, lista por día) y el panel de la cita con
 * sus 3 partes: inmueble, cita y resultado.
 *
 * Controlador: AgentAgenda · Reglas: App\Libraries\Agenda
 * Estilos: .ag-* en style.css · JS: public/js/agenda.js
 */
use App\Libraries\Agenda;
use App\Libraries\FichaTecnica;

$ready = $ready ?? false;
?>

<?= $this->section('styles') ?>
<?= view('partials/banner_preload') ?>
<?= $this->endSection() ?>

<?php if ($ready): ?>
<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/agenda.js') ?>"></script>
<?= $this->endSection() ?>
<?php endif ?>

<?= $this->section('content') ?>

<div class="banner-full-row page-banner" style="background-image:url('<?= base_url('images/breadcromb.webp') ?>');">
    <div class="container">
        <div class="row">
            <div class="col-md-8"><h1 class="h2 page-name float-left text-white text-uppercase">Agenda de citas</h1></div>
        </div>
    </div>
</div>

<div class="full-row ap-page">
    <div class="container">
        <div class="ap-head">
            <div>
                <span class="sec-eyebrow">Área de agentes</span>
                <h2 class="sec-title">Agenda de citas</h2>
                <p class="ap-sub">Compartida por todos los agentes. Cada cita tiene 3 partes: el inmueble, la cita y el resultado.</p>
            </div>
            <nav class="ap-tabs" aria-label="Área de agentes">
                <a class="ap-tab" href="<?= site_url('agente/propiedades') ?>"><i class="ms" aria-hidden="true">location_on</i>Propiedades</a>
                <span class="ap-tab is-active" aria-current="page"><i class="ms" aria-hidden="true">calendar_month</i>Agenda de citas</span>
            </nav>
        </div>

<?php if (! $ready): ?>
        <div class="ap-empty">
            <i class="ms" aria-hidden="true">calendar_month</i>
            <p><b>La agenda está casi lista.</b><br>Falta crear la tabla de citas en la base de datos: ejecuta <code>app/Database/sql/2026-11-appointments.sql</code> en phpMyAdmin.</p>
        </div>
<?php else: ?>
<?php
    $today    = date('Y-m-d');
    $prevWeek = $monday->modify('-7 days')->format('Y-m-d');
    $nextWeek = $monday->modify('+7 days')->format('Y-m-d');
    $base     = static function (array $extra = []) use ($monday, $filter): string {
        $q = array_filter(['semana' => $monday->format('Y-m-d'), 'agente' => $filter], static fn ($v) => $v !== '');

        return site_url('agente/agenda') . '?' . http_build_query(array_merge($q, $extra));
    };

    // Citas por día (Y-m-d => lista con carril)
    $byDay = [];
    foreach ($week as $a) {
        $byDay[substr($a['starts_at'], 0, 10)][] = $a;
    }
    $byDay = array_map([Agenda::class, 'lanes'], $byDay);

    $hours = range(Agenda::FIRST_HOUR, Agenda::LAST_HOUR - 1);

    // Valores del formulario (lo enviado tiene prioridad tras un error)
    $val = static function (string $field, $default = '') use ($current) {
        $fromDb = $current[$field] ?? $default;

        return old($field, $fromDb ?? '');
    };
    $d         = $defaults;
    $result    = (string) old('result', $current['result'] ?? 'pending');
    $isNew     = ! $current;
    $canSee    = $isNew || $editable;
    $code      = $current ? FichaTecnica::code((int) $current['pid']) : '';
    $selPid    = (int) old('pid', (string) $d['pid']);
?>
        <div class="ag-layout">
            <!-- Calendario -->
            <section class="ag-cal" aria-label="Calendario de la semana">
                <div class="ag-cal__head">
                    <h3 class="ag-cal__title"><?= esc(Agenda::weekTitle($monday)) ?></h3>
                    <div class="ag-cal__tools">
                        <form method="get" action="<?= site_url('agente/agenda') ?>" class="ag-who">
                            <input type="hidden" name="semana" value="<?= $monday->format('Y-m-d') ?>">
                            <label class="sr-only" for="ag-agente">Ver citas de</label>
                            <i class="ms" aria-hidden="true">person</i>
                            <select id="ag-agente" name="agente" data-autosubmit>
                                <option value="">Todos los agentes</option>
                                <option value="mias"<?= $filter === 'mias' ? ' selected' : '' ?>>Mis citas</option>
                                <?php foreach ($agents as $a): ?>
                                <option value="<?= (int) $a['uid'] ?>"<?= $filter === (string) $a['uid'] ? ' selected' : '' ?>><?= esc($a['uname']) ?></option>
                                <?php endforeach ?>
                            </select>
                            <noscript><button type="submit" class="ap-btn ap-btn--gold">Ver</button></noscript>
                        </form>
                        <a class="ag-nav" href="<?= esc($base(['semana' => $prevWeek])) ?>" aria-label="Semana anterior"><i class="ms" aria-hidden="true">chevron_left</i></a>
                        <a class="ag-today" href="<?= esc($base(['semana' => $today])) ?>">Hoy</a>
                        <a class="ag-nav" href="<?= esc($base(['semana' => $nextWeek])) ?>" aria-label="Semana siguiente"><i class="ms" aria-hidden="true">chevron_right</i></a>
                    </div>
                </div>

                <!-- Escritorio: semana en columnas -->
                <div class="ag-week" style="--ag-rows: <?= count($hours) ?>">
                    <div class="ag-week__corner" aria-hidden="true"></div>
                    <?php for ($i = 0; $i < 7; $i++): $day = $monday->modify("+{$i} days"); ?>
                    <div class="ag-week__day<?= $day->format('Y-m-d') === $today ? ' is-today' : '' ?>">
                        <small><?= Agenda::DAYS[$i] ?></small><b><?= $day->format('j') ?></b>
                    </div>
                    <?php endfor ?>

                    <div class="ag-week__hours" aria-hidden="true">
                        <?php foreach ($hours as $h): ?><span><?= sprintf('%02d:00', $h) ?></span><?php endforeach ?>
                    </div>
                    <?php for ($i = 0; $i < 7; $i++): $ymd = $monday->modify("+{$i} days")->format('Y-m-d'); ?>
                    <div class="ag-week__col<?= $ymd === $today ? ' is-today' : '' ?><?= $ymd < $today ? ' is-past' : '' ?>" data-day="<?= $ymd ?>" data-first="<?= Agenda::FIRST_HOUR ?>" data-span="<?= count($hours) ?>">
                        <?php foreach ($byDay[$ymd] ?? [] as $a): ?>
                        <?php
                        $pos   = Agenda::position($a['starts_at'], $a['ends_at']);
                        $mine  = Agenda::canEdit($a, $uid);
                        $res   = Agenda::RESULTS[$a['result']] ?? Agenda::RESULTS['pending'];
                        $short = (int) round((strtotime($a['ends_at']) - strtotime($a['starts_at'])) / 60) <= 45;
                        ?>
                        <a class="ag-ev <?= $res[1] ?><?= (int) ($current['apid'] ?? 0) === (int) $a['apid'] ? ' is-open' : '' ?><?= $short ? ' is-short' : '' ?>"
                           href="<?= esc($base(['cita' => $a['apid']])) ?>"
                           style="top: <?= $pos['top'] ?>%; height: <?= $pos['height'] ?>%; left: calc(<?= $a['lane'] ?> * 100% / <?= $a['lanes'] ?>); width: calc(100% / <?= $a['lanes'] ?>);"
                           title="<?= esc(substr($a['starts_at'], 11, 5) . '–' . substr($a['ends_at'], 11, 5) . ' · ' . FichaTecnica::code((int) $a['pid']) . ' · ' . $res[0] . ' · Muestra: ' . ($a['shower_name'] ?? ''), 'attr') ?>">
                            <small class="ag-ev__time"><?= substr($a['starts_at'], 11, 5) ?></small>
                            <b><?= substr(FichaTecnica::code((int) $a['pid']), 5) ?> · <?= esc($a['title'] ?? '') ?></b>
                            <span><?= esc($mine ? $a['client_name'] : Agenda::shortName($a['client_name'])) ?> · <?= esc(strtok((string) $a['shower_name'], ' ')) ?></span>
                        </a>
                        <?php endforeach ?>
                    </div>
                    <?php endfor ?>
                </div>

                <!-- Celular: lista por día -->
                <div class="ag-list">
                    <?php if (! $week): ?>
                    <p class="ag-list__empty">Sin citas esta semana.</p>
                    <?php endif ?>
                    <?php for ($i = 0; $i < 7; $i++): $day = $monday->modify("+{$i} days"); $ymd = $day->format('Y-m-d'); ?>
                    <?php if (empty($byDay[$ymd])) { continue; } ?>
                    <h4 class="ag-list__day<?= $ymd === $today ? ' is-today' : '' ?>"><?= Agenda::DAYS[$i] ?> <?= $day->format('j') ?></h4>
                    <?php foreach ($byDay[$ymd] as $a): $res = Agenda::RESULTS[$a['result']] ?? Agenda::RESULTS['pending']; ?>
                    <a class="ag-item <?= $res[1] ?>" href="<?= esc($base(['cita' => $a['apid']])) ?>#ag-panel">
                        <span class="ag-item__time"><?= substr($a['starts_at'], 11, 5) ?><small><?= substr($a['ends_at'], 11, 5) ?></small></span>
                        <span class="ag-item__body">
                            <b><?= FichaTecnica::code((int) $a['pid']) ?> · <?= esc($a['title'] ?? '') ?></b>
                            <small><?= esc(Agenda::canEdit($a, $uid) ? $a['client_name'] : Agenda::shortName($a['client_name'])) ?> · Muestra <?= esc($a['shower_name'] ?? '') ?></small>
                        </span>
                        <span class="ag-tag"><?= $res[0] ?></span>
                    </a>
                    <?php endforeach ?>
                    <?php endfor ?>
                </div>

                <ul class="ag-legend">
                    <?php foreach (Agenda::RESULTS as $k => [$label, $cls]): ?>
                    <li class="<?= $cls ?>"><?= $label ?></li>
                    <?php endforeach ?>
                    <li class="ag-legend__hint">Haz clic en un horario libre para agendar.</li>
                </ul>
            </section>

            <!-- Panel de la cita -->
            <aside class="ag-panel" id="ag-panel" aria-label="<?= $isNew ? 'Nueva cita' : 'Cita' ?>">
                <div class="ag-panel__head">
                    <h3><?= $isNew ? 'Nueva cita' : ($editable ? 'Editar cita' : 'Detalle de la cita') ?></h3>
                    <?php if (! $isNew): ?>
                    <a class="ag-new" href="<?= esc($base()) ?>#ag-panel"><i class="ms" aria-hidden="true">add</i>Nueva</a>
                    <?php endif ?>
                </div>

                <?php if ($editable): ?>
                <form method="post" action="<?= site_url('agente/agenda/guardar') ?>" class="ag-form" data-agenda-form
                      data-check="<?= site_url('agente/agenda/conflictos') ?>">
                    <?= csrf_field() ?>
                    <?php if (! $isNew): ?><input type="hidden" name="apid" value="<?= (int) $current['apid'] ?>"><?php endif ?>
                    <script type="application/json" data-properties><?= json_encode($properties, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

                    <!-- 1. Inmueble -->
                    <fieldset class="ag-sec">
                        <legend><span class="ag-num">1</span>Inmueble</legend>
                        <label class="ag-field">
                            <span>ID inmueble</span>
                            <select name="pid" required data-pid>
                                <option value="">Elige el inmueble…</option>
                                <?php foreach ($properties as $p): ?>
                                <option value="<?= $p['pid'] ?>"<?= $selPid === $p['pid'] ? ' selected' : '' ?>><?= esc($p['code'] . ' · ' . $p['title']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </label>
                        <?php $p = $properties[$selPid] ?? null; ?>
                        <dl class="ag-info" data-info<?= $p ? '' : ' hidden' ?>>
                            <dt>Dirección y zona</dt><dd data-info-zone class="text-capitalize"><?= esc($p['zone'] ?? '') ?></dd>
                            <dt>Agente responsable</dt><dd data-info-owner><?= esc($p['owner'] ?? '') ?></dd>
                            <dt>Llaves</dt><dd data-info-keys class="ag-keys<?= ! empty($p['warn']) ? ' is-warn' : '' ?>"><i class="ms" aria-hidden="true">key</i><span><?= esc(($p['keys'] ?? '') ?: 'Sin dato') ?></span></dd>
                        </dl>
                    </fieldset>

                    <!-- 2. Cita -->
                    <fieldset class="ag-sec">
                        <legend><span class="ag-num">2</span>Cita</legend>
                        <div class="ag-row">
                            <label class="ag-field"><span>Fecha</span><input type="date" name="date" value="<?= esc(old('date', $d['date']), 'attr') ?>" required data-when></label>
                            <label class="ag-field"><span>Hora</span><input type="time" name="time" value="<?= esc(old('time', $d['time']), 'attr') ?>" step="900" required data-when></label>
                        </div>
                        <div class="ag-row ag-row--dur">
                            <label class="ag-field"><span>Duración</span>
                                <select name="duration" data-when>
                                    <?php foreach (Agenda::DURATIONS as $min => $label): ?>
                                    <option value="<?= $min ?>"<?= (int) old('duration', (string) $d['duration']) === $min ? ' selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach ?>
                                </select>
                            </label>
                            <label class="ag-field"><span>Agente que muestra</span>
                            <select name="shower_uid" required data-when>
                                <?php foreach ($agents as $a): ?>
                                <option value="<?= (int) $a['uid'] ?>"<?= (int) old('shower_uid', (string) $d['shower_uid']) === (int) $a['uid'] ? ' selected' : '' ?>><?= esc($a['uname']) ?><?= (int) $a['uid'] === $uid ? ' (yo)' : '' ?></option>
                                <?php endforeach ?>
                            </select>
                            </label>
                        </div>
                        <div class="ag-row">
                            <label class="ag-field"><span>Cliente prospecto</span><input type="text" name="client_name" value="<?= esc($val('client_name'), 'attr') ?>" maxlength="120" required placeholder="Nombre y apellido" autocomplete="off"></label>
                            <label class="ag-field"><span>WhatsApp / teléfono</span><input type="tel" name="client_phone" value="<?= esc($val('client_phone'), 'attr') ?>" maxlength="30" placeholder="55 1234 5678" autocomplete="off"></label>
                        </div>
                        <label class="ag-field"><span>Correo <small>(opcional)</small></span><input type="email" name="client_email" value="<?= esc($val('client_email'), 'attr') ?>" maxlength="150" placeholder="cliente@correo.com" autocomplete="off"></label>
                        <label class="ag-field"><span>Notas del cliente <small>(opcional)</small></span><input type="text" name="client_notes" value="<?= esc($val('client_notes'), 'attr') ?>" maxlength="500" placeholder="Presupuesto, crédito, qué busca…" autocomplete="off"></label>
                        <p class="ag-private"><i class="ms" aria-hidden="true">lock</i>Teléfono, correo y notas solo los ven quien agenda, quien muestra y el responsable del inmueble.</p>

                        <div class="ag-warn" data-warn role="alert"<?= $conflicts ? '' : ' hidden' ?>>
                            <i class="ms" aria-hidden="true">warning</i>
                            <div data-warn-text><?php foreach ($conflicts as $c): ?><p><?= esc($c) ?></p><?php endforeach ?></div>
                        </div>
                    </fieldset>

                    <!-- 3. Resultado -->
                    <fieldset class="ag-sec">
                        <legend><span class="ag-num ag-num--gold">3</span>Resultado <small>(después de la visita)</small></legend>
                        <div class="ag-results" role="radiogroup" aria-label="Resultado">
                            <?php foreach (Agenda::RESULTS as $k => [$label, $cls]): ?>
                            <label class="<?= $cls ?>"><input type="radio" name="result" value="<?= $k ?>"<?= $result === $k ? ' checked' : '' ?>><span><?= $k === 'pending' ? 'Pendiente' : $label ?></span></label>
                            <?php endforeach ?>
                        </div>
                        <label class="ag-field"><span>Comentarios del cliente</span><textarea name="comments" rows="2" maxlength="2000" placeholder="¿Qué le pareció? Objeciones, dudas…"><?= esc($val('comments')) ?></textarea></label>
                        <div class="ag-row">
                            <label class="ag-field"><span>Próxima acción</span><input type="text" name="next_action" value="<?= esc($val('next_action'), 'attr') ?>" maxlength="160" placeholder="Llamar, enviar opciones…"></label>
                            <label class="ag-field"><span>Fecha</span><input type="date" name="next_date" value="<?= esc($val('next_date'), 'attr') ?>"></label>
                        </div>
                    </fieldset>

                    <?php if ($conflicts): ?>
                    <button type="submit" name="force" value="1" class="ag-save ag-save--warn"><i class="ms" aria-hidden="true">event_available</i>Guardar de todos modos</button>
                    <?php else: ?>
                    <button type="submit" class="ag-save"><i class="ms" aria-hidden="true">event_available</i><?= $isNew ? 'Guardar cita' : 'Guardar cambios' ?></button>
                    <?php endif ?>
                    <p class="ag-foot"><?= $isNew ? 'El agente responsable recibe un correo con cada cita nueva en su inmueble.' : 'Agendó: ' . esc($agents[(int) $current['created_by']]['uname'] ?? '—') . ' · ' . esc(date('d/m/Y H:i', strtotime((string) ($current['created_at'] ?? 'now')))) ?></p>
                </form>

                <?php if (! $isNew): ?>
                <form method="post" action="<?= site_url('agente/agenda/' . (int) $current['apid'] . '/eliminar') ?>" class="ag-delete" data-confirm="¿Eliminar esta cita? No se puede deshacer.">
                    <?= csrf_field() ?>
                    <button type="submit"><i class="ms" aria-hidden="true">delete</i>Eliminar cita</button>
                </form>
                <?php endif ?>

                <?php else: /* Solo ver: la cita es de otros agentes */ ?>
                <?php $res = Agenda::RESULTS[$current['result']] ?? Agenda::RESULTS['pending']; ?>
                <div class="ag-sec">
                    <h4 class="ag-legend-h"><span class="ag-num">1</span>Inmueble</h4>
                    <dl class="ag-info">
                        <dt>ID inmueble</dt><dd><a href="<?= site_url('property/' . $current['pid']) ?>" target="_blank" rel="noopener"><?= $code ?></a> · <?= esc($current['title'] ?? '') ?></dd>
                        <dt>Dirección y zona</dt><dd class="text-capitalize"><?= esc(implode(', ', array_filter([trim((string) $current['location']), trim((string) $current['city'])]))) ?></dd>
                        <dt>Agente responsable</dt><dd><?= esc($current['owner_name'] ?? '') ?></dd>
                        <?php if (isset($properties[(int) $current['pid']]) && $properties[(int) $current['pid']]['keys'] !== ''): $p = $properties[(int) $current['pid']]; ?>
                        <dt>Llaves</dt><dd class="ag-keys<?= $p['warn'] ? ' is-warn' : '' ?>"><i class="ms" aria-hidden="true">key</i><span><?= esc($p['keys']) ?></span></dd>
                        <?php endif ?>
                    </dl>
                </div>
                <div class="ag-sec">
                    <h4 class="ag-legend-h"><span class="ag-num">2</span>Cita</h4>
                    <dl class="ag-info">
                        <dt>Fecha y hora</dt><dd class="ag-cap"><?= esc(Agenda::when($current['starts_at'], $current['ends_at'])) ?></dd>
                        <dt>Agente que muestra</dt><dd><?= esc($current['shower_name'] ?? '') ?></dd>
                        <dt>Cliente</dt><dd><?= esc(Agenda::shortName($current['client_name'])) ?></dd>
                    </dl>
                    <p class="ag-private"><i class="ms" aria-hidden="true">lock</i>Los datos de contacto del cliente solo los ven quien agendó, quien muestra y el responsable del inmueble.</p>
                </div>
                <div class="ag-sec">
                    <h4 class="ag-legend-h"><span class="ag-num ag-num--gold">3</span>Resultado</h4>
                    <span class="ag-tag ag-tag--lg <?= $res[1] ?>"><?= $res[0] ?></span>
                </div>
                <?php endif ?>
            </aside>
        </div>
<?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
