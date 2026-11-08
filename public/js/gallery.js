/* ================================================================
   Carrusel de fotos del detalle de propiedad ([data-gallery])
   Reemplaza a LayerSlider. El desplazamiento lo hace el navegador
   (CSS scroll-snap: se desliza con el dedo y funciona sin JS); este
   script solo agrega flechas, puntos, contador y teclado.
   Fotos: solo la primera viene con src; las demás traen data-src /
   data-srcset y se descargan al acercarse a ellas (la siguiente a la
   que se ve). loading="lazy" no basta: dentro de un carrusel
   horizontal el navegador las descargaba todas de una vez.
   Vista: app/Views/property/detail.php · Estilos: .gallery en style.css
   ================================================================ */
(function () {
    'use strict';

    function init(gallery) {
        var track  = gallery.querySelector('.gallery__track');
        var slides = Array.prototype.slice.call(gallery.querySelectorAll('.gallery__slide'));
        var prev   = gallery.querySelector('[data-gallery-prev]');
        var next   = gallery.querySelector('[data-gallery-next]');
        var count  = gallery.querySelector('[data-gallery-current]');
        var dots   = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-dot]'));
        var current = 0;

        if (!track || slides.length === 0) { return; }

        // Descargar la foto index (si aún no se ha pedido)
        function warm(index) {
            var slide = slides[index];
            var img = slide && slide.querySelector('img[data-src]');
            if (!img) { return; }
            if (img.dataset.srcset) { img.srcset = img.dataset.srcset; }
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            img.removeAttribute('data-srcset');
        }

        function update(index, warmNext) {
            current = Math.max(0, Math.min(slides.length - 1, index));
            if (count) { count.textContent = String(current + 1); }
            dots.forEach(function (dot, i) {
                if (i === current) { dot.setAttribute('aria-current', 'true'); }
                else { dot.removeAttribute('aria-current'); }
            });
            if (prev) { prev.disabled = current === 0; }
            if (next) { next.disabled = current === slides.length - 1; }
            warm(current);
            if (warmNext !== false) { warm(current + 1); }
        }

        function goTo(index) {
            index = Math.max(0, Math.min(slides.length - 1, index));
            track.scrollTo({ left: slides[index].offsetLeft - track.offsetLeft, behavior: 'smooth' });
            update(index);
        }

        // Saber qué foto se ve al deslizar con el dedo o con el panel táctil
        var ticking = false;
        track.addEventListener('scroll', function () {
            if (ticking) { return; }
            ticking = true;
            window.requestAnimationFrame(function () {
                ticking = false;
                var width = track.clientWidth || 1;
                update(Math.round(track.scrollLeft / width));
            });
        }, { passive: true });

        if (prev) { prev.addEventListener('click', function () { goTo(current - 1); }); }
        if (next) { next.addEventListener('click', function () { goTo(current + 1); }); }
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { goTo(i); });
        });

        // Teclado: flechas cuando el carrusel tiene el foco
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); goTo(current + 1); }
            if (e.key === 'ArrowLeft')  { e.preventDefault(); goTo(current - 1); }
        });

        // Al girar el teléfono o cambiar el tamaño, mantener la misma foto
        window.addEventListener('resize', function () {
            track.scrollLeft = slides[current].offsetLeft - track.offsetLeft;
        });

        gallery.classList.add('is-ready');
        update(0, false);
        // Cuando la página terminó de cargar, adelantar la segunda foto
        if (document.readyState === 'complete') { warm(1); }
        else { window.addEventListener('load', function () { warm(1); }); }
    }

    function initAll() {
        document.querySelectorAll('[data-gallery]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
