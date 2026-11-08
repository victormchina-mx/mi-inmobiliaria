/* ================================================================
   "Lo que hay cerca" en el detalle de la propiedad ([data-nearby])
   Pide el cuadro a /property/{id}/cerca (Property::nearby) cuando el
   visitante se acerca a esa parte de la página, y maneja las pestañas
   por categoría y "Ver todos". Si no hay datos, el cuadro desaparece.
   Vista del cuadro: app/Views/partials/nearby.php · Estilos: .nb-*
   ================================================================ */
(function () {
    'use strict';

    function setup(box) {
        var list    = box.querySelector('[data-nb-list]');
        var more    = box.querySelector('[data-nb-more]');
        var title   = box.querySelector('[data-nb-list-title]');
        var tabs    = Array.prototype.slice.call(box.querySelectorAll('.nb-tab'));
        var visible = parseInt(list ? list.getAttribute('data-nb-visible') : '6', 10) || 6;
        var current = 'all';
        var expanded = false;

        function render() {
            if (!list) { return; }
            var shown = 0, matching = 0;
            Array.prototype.forEach.call(list.children, function (li) {
                var match = current === 'all' || li.getAttribute('data-cat') === current;
                if (match) { matching++; }
                var show = match && (expanded || shown < visible);
                if (show) { shown++; }
                li.hidden = !show;
            });
            if (more) {
                more.hidden = matching <= visible;
                more.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                more.querySelector('[data-nb-more-label]').textContent = expanded ? 'Ver menos' : 'Ver los ' + matching + ' lugares';
                more.classList.toggle('is-open', expanded);
            }
        }

        function select(cat) {
            current = cat;
            expanded = false;
            tabs.forEach(function (tab) {
                var on = tab.getAttribute('data-nb-tab') === cat;
                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            if (title) {
                var active = tabs.filter(function (t) { return t.classList.contains('is-active'); })[0];
                title.textContent = cat === 'all' || !active ? 'Más cercanos' : active.getAttribute('data-nb-label');
            }
            render();
        }

        box.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-nb-tab]');
            if (trigger) {
                select(trigger.getAttribute('data-nb-tab'));
                // Desde una tarjeta: llevar la vista a la lista
                if (trigger.classList.contains('nb-cat') && list) {
                    list.parentNode.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                return;
            }
            if (e.target.closest('[data-nb-more]')) {
                expanded = !expanded;
                render();
            }
        });
    }

    function load(box) {
        var url = box.getAttribute('data-nearby');
        fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : { ok: false }; })
            .then(function (data) {
                if (!data || !data.ok || !data.html) { box.remove(); return; }
                box.innerHTML = data.html;
                box.removeAttribute('aria-busy');
                box.classList.add('is-loaded');
                setup(box);
            })
            .catch(function () { box.remove(); });
    }

    function init() {
        document.querySelectorAll('[data-nearby]').forEach(function (box) {
            if (!('IntersectionObserver' in window)) { load(box); return; }
            var io = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) { io.disconnect(); load(box); }
            }, { rootMargin: '800px 0px' });
            io.observe(box);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
