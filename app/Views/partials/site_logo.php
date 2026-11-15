<?php
/**
 * Logo del sitio: ícono de la casa dorada (images/logo/inmobiliaria-icon.png)
 * con "Inmobiliaria" y el lema en texto (nítido en cualquier tamaño).
 *
 * Variables: $light (true en fondos oscuros, como el pie de página)
 */
$light = ! empty($light);
?>
<span class="site-logo<?= $light ? ' site-logo--light' : '' ?>">
    <img class="site-logo__icon" src="<?= base_url('images/logo/inmobiliaria-icon.png') ?>" alt="" width="46" height="46">
    <span class="site-logo__text">
        <b>Inmobiliaria</b>
        <small>Publica y administra tus propiedades</small>
    </span>
</span>
