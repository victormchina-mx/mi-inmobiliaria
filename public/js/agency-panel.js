/* ================================================================
   Mi inmobiliaria (app/Views/agent/agency.php)
   - Colores: muestras, selector y #hex sincronizados; la vista previa de
     la ficha cambia en vivo.
   - Confirmación antes de quitar a un agente.
   ================================================================ */
(function () {
    'use strict';

    var prev = document.querySelector('[data-ma-preview]');
    var valid = function (v) { return /^#[0-9a-f]{6}$/i.test(v); };

    function setColor(k, v) {
        if (!valid(v)) { return; }
        v = v.toUpperCase();
        var hex = document.querySelector('[data-ma-hex="' + k + '"]');
        var pick = document.querySelector('[data-ma-pick="' + k + '"]');
        if (hex && hex.value.toUpperCase() !== v) { hex.value = v; }
        if (pick) { pick.value = v; }
        document.querySelectorAll('[data-ma-sw="' + k + '"]').forEach(function (b) {
            b.classList.toggle('is-on', b.getAttribute('data-color').toUpperCase() === v);
        });
        if (prev) { prev.style.setProperty(k === 'primary' ? '--p' : '--a', v); }
    }

    ['primary', 'accent'].forEach(function (k) {
        document.querySelectorAll('[data-ma-sw="' + k + '"]').forEach(function (b) {
            b.addEventListener('click', function () { setColor(k, b.getAttribute('data-color')); });
        });
        var pick = document.querySelector('[data-ma-pick="' + k + '"]');
        if (pick) { pick.addEventListener('input', function () { setColor(k, pick.value); }); }
        var hex = document.querySelector('[data-ma-hex="' + k + '"]');
        if (hex) { hex.addEventListener('input', function () { setColor(k, hex.value.trim()); }); }
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    });
})();
