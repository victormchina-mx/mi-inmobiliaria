<?php
/**
 * Mensaje "¿Te interesa esta propiedad?" con el botón Contactar y una X
 * para cerrarlo. Aparece con efecto 2 s después de verse:
 *  - sobre la última foto del carrusel del detalle (public/js/gallery.js);
 *  - pequeño, abajo a la derecha del mapa de la página Ubicación
 *    (public/js/location.js lo coloca como control del mapa).
 * La animación se activa con la clase .cta-on en un elemento que lo contiene.
 * Estilos: .cta-card en public/css/style.css
 *
 * Variables: $href (a dónde lleva Contactar), $small (versión pequeña).
 */
$small = ! empty($small);
?>
<div class="cta-card<?= $small ? ' cta-card--small' : '' ?>" role="complementary" aria-label="Contactar al agente">
    <button type="button" class="cta-card__close" data-cta-close aria-label="Cerrar">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <p class="cta-card__title">¿Te interesa esta propiedad?</p>
    <p class="cta-card__text">Habla con el agente y agenda tu visita.</p>
    <a href="<?= esc($href, 'attr') ?>" class="cta-card__btn" data-cta-contact>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Contactar
    </a>
</div>
