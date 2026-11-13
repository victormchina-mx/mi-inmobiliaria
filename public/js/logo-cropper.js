/* ================================================================
   Recorte del logo en un círculo (partials/logo_cropper.php)
   - Al elegir un logo (o con el actual) se muestra dentro de un círculo.
   - Arrastrar mueve la imagen; el control (o la rueda) la acerca o aleja.
   - Al guardar se envía en "logo_icon" un PNG cuadrado de 256 px con lo
     que se ve en el círculo (solo si se cambió algo). El servidor lo
     guarda como "{logo}-icon.png" (App\Libraries\Agency::saveIcon).
   ================================================================ */
(function () {
    'use strict';

    document.querySelectorAll('[data-logo-cropper]').forEach(function (box) {
        var view   = box.querySelector('[data-lc-view]');
        var img    = box.querySelector('[data-lc-img]');
        var zoom   = box.querySelector('[data-lc-zoom]');
        var out    = box.querySelector('[data-lc-out]');
        var minis  = Array.prototype.slice.call(box.querySelectorAll('[data-lc-mini]'));
        var empty  = box.querySelector('[data-lc-empty]');
        var input  = document.getElementById(box.getAttribute('data-input'));
        var form   = box.closest('form');
        var SIZE   = 256;   // lado del PNG que se guarda

        var st = { w: 0, h: 0, base: 1, s: 1, x: 0, y: 0, ready: false, dirty: false };

        function V() { return view.clientWidth; }

        function apply() {
            img.style.width  = (st.w * st.s) + 'px';
            img.style.height = (st.h * st.s) + 'px';
            img.style.transform = 'translate(' + st.x + 'px,' + st.y + 'px)';
            drawMinis();
        }

        // Que la imagen no se salga por completo del círculo
        function clamp() {
            var v = V(), w = st.w * st.s, h = st.h * st.s, m = v * 0.25;
            st.x = Math.min(v - m, Math.max(m - w, st.x));
            st.y = Math.min(v - m, Math.max(m - h, st.y));
        }

        function center() {
            var v = V();
            st.s = st.base;
            st.x = (v - st.w * st.s) / 2;
            st.y = (v - st.h * st.s) / 2;
            zoom.value = 1;
            apply();
        }

        function load(src, isNew) {
            var probe = new Image();
            probe.onload = function () {
                st.w = probe.naturalWidth;
                st.h = probe.naturalHeight;
                // Escala base: el logo completo cabe en el círculo (luego se acerca)
                st.base = V() / Math.max(st.w, st.h);
                img.src = src;
                st.ready = true;
                st.dirty = !!isNew;
                box.classList.add('is-ready');
                if (empty) { empty.hidden = true; }
                center();
            };
            probe.src = src;
        }

        function render(size) {
            var c = document.createElement('canvas');
            c.width = c.height = size;
            var k = size / V();
            c.getContext('2d').drawImage(img, st.x * k, st.y * k, st.w * st.s * k, st.h * st.s * k);
            return c;
        }

        function drawMinis() {
            if (!st.ready) { return; }
            minis.forEach(function (m) {
                var c = render(m.width);
                var ctx = m.getContext('2d');
                ctx.clearRect(0, 0, m.width, m.height);
                ctx.drawImage(c, 0, 0);
            });
        }

        // Zoom: de 0.5× a 6× la escala en que cabe completo, hacia el centro
        function setZoom(z) {
            var v = V(), old = st.s;
            st.s = st.base * z;
            st.x = v / 2 - (v / 2 - st.x) * (st.s / old);
            st.y = v / 2 - (v / 2 - st.y) * (st.s / old);
            clamp();
            st.dirty = true;
            apply();
        }
        zoom.addEventListener('input', function () { setZoom(parseFloat(zoom.value)); });
        view.addEventListener('wheel', function (e) {
            if (!st.ready) { return; }
            e.preventDefault();
            var z = Math.min(6, Math.max(0.5, parseFloat(zoom.value) * (e.deltaY < 0 ? 1.08 : 0.92)));
            zoom.value = z;
            setZoom(z);
        }, { passive: false });

        // Arrastrar (mouse y dedo)
        var drag = null;
        view.addEventListener('pointerdown', function (e) {
            if (!st.ready) { return; }
            drag = { px: e.clientX, py: e.clientY, x: st.x, y: st.y };
            view.setPointerCapture(e.pointerId);
            view.classList.add('is-dragging');
        });
        view.addEventListener('pointermove', function (e) {
            if (!drag) { return; }
            st.x = drag.x + (e.clientX - drag.px);
            st.y = drag.y + (e.clientY - drag.py);
            clamp();
            st.dirty = true;
            apply();
        });
        ['pointerup', 'pointercancel'].forEach(function (ev) {
            view.addEventListener(ev, function () { drag = null; view.classList.remove('is-dragging'); });
        });

        box.querySelector('[data-lc-center]').addEventListener('click', function () {
            if (!st.ready) { return; }
            center();
            st.dirty = true;
        });

        // Logo nuevo elegido en el campo de archivo
        if (input) {
            input.addEventListener('change', function () {
                var f = input.files && input.files[0];
                if (!f || !/^image\/(png|jpeg|webp)$/.test(f.type)) { return; }
                var r = new FileReader();
                r.onload = function () { load(r.result, true); };
                r.readAsDataURL(f);
            });
        }

        // Logo actual (mismo sitio: se puede volver a recortar)
        if (box.getAttribute('data-src')) { load(box.getAttribute('data-src'), false); }

        if (form) {
            form.addEventListener('submit', function () {
                out.value = st.ready && st.dirty ? render(SIZE).toDataURL('image/png') : '';
            });
        }
    });
})();
