<?= $this->extend('layouts/main') ?>

<?php
/**
 * Landing page de la plataforma (/plataforma). Controlador: Plataforma.
 * Estilos: public/css/landing.css (prefijo .lp-).
 *
 * Las ilustraciones del encabezado (calendario, ficha y celular) son HTML/CSS
 * decorativo; las fotos y los números salen de la base de datos.
 */
use App\Libraries\FichaTecnica;
use App\Libraries\Labels;

$publishUrl = site_url('my-properties/add');
$signupUrl  = $loggedIn ? $publishUrl : site_url('register');
$hero       = $showcase[0] ?? null;
$heroImg    = $hero ? thumb_url('property', $hero['pimage'], 800) : '';
$money      = static fn ($v): string => '$' . number_format((float) $v, 0, '.', ',');
?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/landing.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ============ Encabezado ============ -->
<section class="lp-hero">
    <div class="container">
        <div class="lp-hero__grid">
            <div class="lp-hero__copy">
                <?php if ($offer): ?>
                <a class="lp-launch" href="#fundador"><span>Lanzamiento</span>Gratis hasta el <?= esc($offer['freeUntil']) ?><i class="ms" aria-hidden="true">arrow_forward</i></a>
                <?php endif ?>
                <span class="lp-eyebrow">Plataforma inmobiliaria</span>
                <h1 class="lp-hero__title">Publica y administra <em>tus propiedades</em></h1>
                <p class="lp-hero__lead">El portal donde compradores, agentes, inmobiliarias y constructoras se encuentran. Propiedades con fotos, ubicación y contacto directo, y herramientas para que tu equipo venda más y se organice mejor.</p>
                <div class="lp-hero__cta">
                    <a class="lp-btn lp-btn--gold" href="<?= site_url('property') ?>">Ver propiedades<i class="ms" aria-hidden="true">arrow_forward</i></a>
                    <a class="lp-btn lp-btn--ghost" href="<?= $publishUrl ?>">Publicar mi propiedad</a>
                </div>
                <ul class="lp-hero__checks">
                    <li><i class="ms" aria-hidden="true">check_circle</i>Particulares publican gratis</li>
                    <li><i class="ms" aria-hidden="true">check_circle</i>Inmobiliarias, constructoras y agentes</li>
                    <li><i class="ms" aria-hidden="true">check_circle</i>Cada inmobiliaria con su logo</li>
                </ul>
            </div>

            <!-- Ilustración: agenda del mes, ficha técnica y app -->
            <div class="lp-hero__art" aria-hidden="true">
                <div class="lp-mock lp-mock--cal">
                    <div class="lp-mock__bar"><span></span><span></span><span></span><b>Agenda de citas</b></div>
                    <div class="lp-cal">
                        <div class="lp-cal__head"><b>Noviembre</b><small>Semana · <u>Mes</u></small></div>
                        <div class="lp-cal__grid">
                            <?php foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $d): ?><i class="lp-cal__dow"><?= $d ?></i><?php endforeach ?>
                            <?php
                            $marks = [3 => ['is-blue', 'is-green'], 5 => ['is-orange'], 10 => ['is-blue'], 12 => ['is-gray'], 17 => ['is-blue'], 18 => ['is-blue', 'is-blue', 'is-green'], 20 => ['is-blue'], 24 => ['is-gray'], 26 => ['is-green'], 30 => ['is-blue']];
                            for ($n = -5; $n <= 36; $n++):
                                $in = $n >= 1 && $n <= 30;
                            ?>
                            <span class="lp-cal__day<?= $in ? '' : ' is-out' ?><?= $n === 18 ? ' is-today' : '' ?>">
                                <em><?= $in ? $n : ($n < 1 ? 31 + $n : $n - 30) ?></em>
                                <?php foreach ($marks[$n] ?? [] as $c): ?><s class="<?= $c ?>"></s><?php endforeach ?>
                            </span>
                            <?php endfor ?>
                        </div>
                    </div>
                </div>

                <div class="lp-mock lp-mock--ficha">
                    <div class="lp-ficha__photo"<?= $heroImg ? ' style="background-image:url(\'' . esc($heroImg, 'attr') . '\')"' : '' ?>></div>
                    <div class="lp-ficha__body">
                        <small><?= $hero ? esc(Labels::propertyType($hero['type'])) . ' en venta' : 'Casa en venta' ?></small>
                        <b><?= $hero ? esc(mb_strimwidth((string) $hero['title'], 0, 34, '…')) : 'Ficha técnica' ?></b>
                        <span class="lp-ficha__price"><?= $hero ? $money($hero['price']) : '$2,480,000' ?></span>
                        <div class="lp-ficha__foot"><i class="ms">qr_code_2</i><span><?= $hero ? FichaTecnica::code((int) $hero['pid']) : 'A-0042' ?></span></div>
                    </div>
                </div>

                <div class="lp-mock lp-mock--phone">
                    <div class="lp-phone__notch"></div>
                    <b class="lp-phone__title">Hoy</b>
                    <div class="lp-phone__item is-blue"><em>10:00</em><span><b>Visita</b><small>Casa · Toluca</small></span></div>
                    <div class="lp-phone__item is-green"><em>12:30</em><span><b>Le interesó</b><small>Terreno · Metepec</small></span></div>
                    <div class="lp-phone__item is-orange"><em>17:00</em><span><b>Oferta</b><small>Depto · Centro</small></span></div>
                    <div class="lp-phone__nav"><i class="ms">home</i><i class="ms">apartment</i><i class="ms is-on">calendar_month</i></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($stats): ?>
<!-- ============ Números reales ============ -->
<section class="lp-stats">
    <div class="container">
        <ul class="lp-stats__list">
            <?php if (! empty($stats['properties'])): ?><li><b><?= number_format($stats['properties']) ?></b><span>propiedades disponibles</span></li><?php endif ?>
            <?php if (! empty($stats['agencies'])): ?><li><b><?= number_format($stats['agencies']) ?></b><span><?= $stats['agencies'] === 1 ? 'inmobiliaria' : 'inmobiliarias' ?></span></li><?php endif ?>
            <?php if (! empty($stats['agents'])): ?><li><b><?= number_format($stats['agents']) ?></b><span>agentes</span></li><?php endif ?>
            <li><b>24/7</b><span>tu información en línea</span></li>
        </ul>
    </div>
</section>
<?php endif ?>

<?php if ($offer): ?>
<!-- ============ Oferta de lanzamiento: Socio fundador ============ -->
<section class="lp-sec lp-offer" id="fundador">
    <div class="container">
        <div class="lp-offer__box">
            <div class="lp-offer__head">
                <span class="lp-seal" aria-hidden="true"><i class="ms">workspace_premium</i></span>
                <span class="lp-eyebrow lp-eyebrow--dark">Oferta de lanzamiento</span>
                <h2 class="lp-h2">Sé Socio fundador</h2>
                <p class="lp-lead">Agentes, inmobiliarias y constructoras que se registren <b>antes del <?= esc($offer['ends']) ?></b> usan la plataforma completa <b>gratis hasta el <?= esc($offer['freeUntil']) ?></b>.</p>
                <div class="lp-count"><b><?= (int) $offer['daysLeft'] ?></b><span><?= $offer['daysLeft'] === 1 ? 'día' : 'días' ?> para<br>registrarte</span></div>
            </div>
            <ul class="lp-perks">
                <li>
                    <i class="ms" aria-hidden="true">event_available</i>
                    <b>6 meses gratis</b>
                    <span>Agenda de citas, fichas técnicas, app para agentes y panel de tu inmobiliaria, sin costo hasta el <?= esc($offer['freeUntil']) ?>.</span>
                </li>
                <li>
                    <i class="ms" aria-hidden="true">workspace_premium</i>
                    <b>Insignia de Socio fundador</b>
                    <span>Un distintivo que reconoce a quienes confiaron desde el inicio.</span>
                </li>
                <li>
                    <i class="ms" aria-hidden="true">upload</i>
                    <b>Te subimos tus propiedades</b>
                    <span>Si eres inmobiliaria o constructora, mándanos tus fichas y cargamos tus primeras propiedades por ti.</span>
                </li>
                <li>
                    <i class="ms" aria-hidden="true">home</i>
                    <b>Particulares, gratis siempre</b>
                    <span>Si vendes o rentas tu propia casa, publicar no te cuesta nada, sin fecha límite.</span>
                </li>
            </ul>
            <div class="lp-offer__cta">
                <a class="lp-btn lp-btn--gold" href="<?= $signupUrl ?>"><?= $loggedIn ? 'Publicar mi propiedad' : 'Registrarme como Socio fundador' ?><i class="ms" aria-hidden="true">arrow_forward</i></a>
                <a class="lp-link" href="<?= site_url('contact') ?>">Quiero que suban mis propiedades<i class="ms" aria-hidden="true">arrow_forward</i></a>
            </div>
            <p class="lp-fine">Sin tarjeta y sin compromiso. Aplica a cuentas de agente, inmobiliaria o constructora registradas hasta el <?= esc($offer['ends']) ?>. Antes de que termine el periodo gratuito te avisaremos con anticipación los planes disponibles.</p>
        </div>
    </div>
</section>
<?php endif ?>

<!-- ============ Para quienes buscan ============ -->
<section class="lp-sec">
    <div class="container">
        <div class="lp-split">
            <div>
                <span class="lp-eyebrow lp-eyebrow--dark">Para quienes buscan</span>
                <h2 class="lp-h2">Encuentra la propiedad ideal</h2>
                <p class="lp-lead">Casas, departamentos, terrenos, locales, oficinas y desarrollos en preventa de inmobiliarias reconocidas, constructoras y agentes independientes. Cada propiedad trae fotos, características, ubicación y los datos del agente para que lo contactes directamente.</p>
                <ul class="lp-list">
                    <li><i class="ms" aria-hidden="true">search</i><span><b>Búsqueda por tipo, estado y ciudad</b>Filtra y encuentra rápido lo que buscas.</span></li>
                    <li><i class="ms" aria-hidden="true">map</i><span><b>Ubicación en el mapa</b>Conoce la zona y lo que hay cerca.</span></li>
                    <li><i class="ms" aria-hidden="true">calculate</i><span><b>Calculadora de cuotas</b>Planea tu compra antes de visitar.</span></li>
                </ul>
                <a class="lp-link" href="<?= site_url('property') ?>">Explorar propiedades<i class="ms" aria-hidden="true">arrow_forward</i></a>
            </div>
            <?php if ($showcase): ?>
            <div class="lp-gallery lp-gallery--<?= count($showcase) ?>">
                <?php foreach ($showcase as $i => $p): ?>
                <a class="lp-prop" href="<?= site_url('property/' . (int) $p['pid']) ?>">
                    <img src="<?= esc(thumb_url('property', $p['pimage'], $i === 0 ? 800 : 400), 'attr') ?>" alt="<?= esc($p['title'], 'attr') ?>" loading="lazy" decoding="async">
                    <span class="lp-prop__info">
                        <b><?= $money($p['price']) ?></b>
                        <small><?= esc(trim(Labels::propertyType($p['type']) . ' · ' . $p['city'], ' ·')) ?></small>
                    </span>
                </a>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>
</section>

<!-- ============ Para agentes ============ -->
<section class="lp-sec lp-sec--soft" id="agentes">
    <div class="container">
        <div class="lp-center">
            <span class="lp-eyebrow lp-eyebrow--dark">Para agentes</span>
            <h2 class="lp-h2">Tu oficina en el bolsillo</h2>
            <p class="lp-lead">Todo lo que necesitas para vender más y organizarte mejor, en un solo lugar.</p>
        </div>
        <div class="lp-cards">
            <article class="lp-card">
                <span class="lp-card__ic"><i class="ms" aria-hidden="true">calendar_month</i></span>
                <h3>Agenda de citas compartida</h3>
                <p>Vista por semana y por mes. Avisa si dos citas se empalman y registra el resultado de cada visita: le interesó, oferta o descartado, cada uno con su color.</p>
            </article>
            <article class="lp-card">
                <span class="lp-card__ic"><i class="ms" aria-hidden="true">description</i></span>
                <h3>Fichas técnicas profesionales</h3>
                <p>Tres diseños listos con fotos, precio, características y código QR. Descárgalas como imagen y compártelas en redes y WhatsApp.</p>
            </article>
            <article class="lp-card">
                <span class="lp-card__ic"><i class="ms" aria-hidden="true">smartphone</i></span>
                <h3>App para agentes</h3>
                <p>Tus propiedades, fotos y citas desde el celular. Toma la foto, súbela y agenda la visita sin pasar por la computadora.</p>
            </article>
        </div>
        <ul class="lp-mini">
            <li><i class="ms" aria-hidden="true">key</i>Control de llaves por inmueble</li>
            <li><i class="ms" aria-hidden="true">lock</i>Datos del cliente privados</li>
            <li><i class="ms" aria-hidden="true">mail</i>Aviso por correo de cada cita nueva</li>
        </ul>
    </div>
</section>

<!-- ============ Para inmobiliarias ============ -->
<section class="lp-sec lp-sec--dark" id="inmobiliarias">
    <div class="container">
        <div class="lp-split lp-split--rev">
            <div>
                <span class="lp-eyebrow">Para inmobiliarias y constructoras</span>
                <h2 class="lp-h2 lp-h2--light">Tu marca, tu equipo, tu información</h2>
                <p class="lp-lead lp-lead--light">Tu empresa tiene su propio espacio dentro del portal: tus propiedades y desarrollos se ven junto a los demás, pero tu equipo y tus datos son solo tuyos.</p>
                <a class="lp-btn lp-btn--gold" href="<?= site_url('contact') ?>">Quiero sumar mi empresa<i class="ms" aria-hidden="true">arrow_forward</i></a>
            </div>
            <ul class="lp-feats">
                <li><i class="ms" aria-hidden="true">palette</i><b>Tu logo y tus colores</b><span>En cada propiedad del portal y en cada ficha técnica.</span></li>
                <li><i class="ms" aria-hidden="true">badge</i><b>Un encargado</b><span>Administra a los agentes y la identidad de la inmobiliaria.</span></li>
                <li><i class="ms" aria-hidden="true">groups</i><b>Información solo para tu equipo</b><span>Cada agente ve únicamente las propiedades y citas de su inmobiliaria.</span></li>
                <li><i class="ms" aria-hidden="true">apartment</i><b>Desarrollos y preventas</b><span>Las constructoras publican sus casas y departamentos con su logo, ante todos los compradores del portal.</span></li>
            </ul>
        </div>
    </div>
</section>

<!-- ============ Cómo funciona ============ -->
<section class="lp-sec">
    <div class="container">
        <div class="lp-center">
            <span class="lp-eyebrow lp-eyebrow--dark">Cómo funciona</span>
            <h2 class="lp-h2">Empieza en tres pasos</h2>
        </div>
        <ol class="lp-steps">
            <li><span>1</span><b>Crea tu cuenta</b><p>Regístrate como usuario, agente o constructor en un par de minutos.</p></li>
            <li><span>2</span><b>Publica tus propiedades</b><p>Sube fotos, precio, ubicación y características.</p></li>
            <li><span>3</span><b>Comparte y agenda</b><p>Genera la ficha técnica, compártela y organiza las visitas.</p></li>
        </ol>
    </div>
</section>

<!-- ============ Preguntas frecuentes ============ -->
<section class="lp-sec lp-sec--soft">
    <div class="container">
        <div class="lp-faq">
            <div>
                <span class="lp-eyebrow lp-eyebrow--dark">Preguntas frecuentes</span>
                <h2 class="lp-h2">¿Tienes dudas?</h2>
                <p class="lp-lead">Si no encuentras tu respuesta, <a href="<?= site_url('contact') ?>">escríbenos</a>.</p>
            </div>
            <div class="lp-faq__list">
                <details open>
                    <summary>¿Cuánto cuesta?</summary>
                    <p>Para particulares que venden o rentan su propia propiedad, publicar es gratis siempre.<?php if ($offer): ?> Agentes, inmobiliarias y constructoras que se registren antes del <?= esc($offer['ends']) ?> la usan gratis hasta el <?= esc($offer['freeUntil']) ?>; antes de que termine ese periodo avisaremos los planes con anticipación.<?php else: ?> Para agentes, inmobiliarias y constructoras, <a href="<?= site_url('contact') ?>">escríbenos</a> y te contamos los planes.<?php endif ?></p>
                </details>
                <details>
                    <summary>Soy constructora, ¿puedo publicar mis desarrollos?</summary>
                    <p>Sí. Regístrate como Constructor y publica tus casas y departamentos, incluso en preventa. Si lo prefieres, mándanos tus fichas y los cargamos por ti.</p>
                </details>
                <details>
                    <summary>¿Puedo publicar si soy agente independiente?</summary>
                    <p>Sí. Tus propiedades aparecen en el portal aunque no pertenezcas a una inmobiliaria.</p>
                </details>
                <details>
                    <summary>¿Los demás agentes ven a mis clientes?</summary>
                    <p>No. El nombre y las notas del cliente solo los ven quien agenda la cita, quien la muestra y el responsable del inmueble.</p>
                </details>
                <details>
                    <summary>¿Puedo usar el logo de mi inmobiliaria?</summary>
                    <p>Sí. Cada inmobiliaria tiene su logo y sus colores, que aparecen en sus propiedades y en sus fichas técnicas.</p>
                </details>
                <details>
                    <summary>¿Cómo contacto a un agente?</summary>
                    <p>En el detalle de cada propiedad están los datos del agente responsable para que le escribas o le llames directamente.</p>
                </details>
            </div>
        </div>
    </div>
</section>

<!-- ============ Llamado final ============ -->
<section class="lp-final">
    <div class="container">
        <div class="lp-final__box">
            <div>
                <h2>¿Listo para empezar?</h2>
                <?php if ($offer): ?>
                <p>Regístrate antes del <?= esc($offer['ends']) ?> y úsala gratis hasta el <?= esc($offer['freeUntil']) ?>.</p>
                <?php else: ?>
                <p>Crea tu cuenta y publica tu primera propiedad hoy.</p>
                <?php endif ?>
            </div>
            <div class="lp-final__cta">
                <a class="lp-btn lp-btn--gold" href="<?= $signupUrl ?>"><?= $loggedIn ? 'Publicar mi propiedad' : 'Crear mi cuenta' ?><i class="ms" aria-hidden="true">arrow_forward</i></a>
                <a class="lp-btn lp-btn--ghost" href="<?= site_url('property') ?>">Ver propiedades</a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
