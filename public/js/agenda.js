/* ================================================================
   Agenda de citas (app/Views/agent/agenda.php)
   - Filtro de agente: se aplica al elegirlo.
   - Clic en un horario libre del calendario: llena fecha y hora.
   - Sección 1: datos del inmueble elegido (dirección, responsable, llaves).
   - Aviso en vivo si la cita choca con otra (mismo inmueble o agente).
   - Confirmación antes de eliminar.
   ================================================================ */
(function () {
    'use strict';

    // ---- Filtro de agente ----
    document.querySelectorAll('[data-autosubmit]').forEach(function (sel) {
        sel.addEventListener('change', function () { sel.form.submit(); });
    });

    // ---- Eliminar ----
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    });

    var form = document.querySelector('[data-agenda-form]');
    if (!form) { return; }

    var props = {};
    try { props = JSON.parse(form.querySelector('[data-properties]').textContent) || {}; } catch (e) { props = {}; }

    // ---- Sección 1: inmueble ----
    var pidSel = form.querySelector('[data-pid]');
    var info   = form.querySelector('[data-info]');
    function showProperty() {
        var p = props[pidSel.value];
        info.hidden = !p;
        if (!p) { return; }
        info.querySelector('[data-info-zone]').textContent  = p.zone || 'Sin ubicación';
        info.querySelector('[data-info-owner]').textContent = p.owner || '';
        var keys = info.querySelector('[data-info-keys]');
        keys.querySelector('span').textContent = p.keys || 'Sin dato';
        keys.classList.toggle('is-warn', !!p.warn);
    }
    pidSel.addEventListener('change', function () { showProperty(); check(); });

    // ---- Clic en un horario libre ----
    var dateIn = form.querySelector('[name="date"]');
    var timeIn = form.querySelector('[name="time"]');
    document.querySelectorAll('[data-day]').forEach(function (col) {
        col.addEventListener('click', function (e) {
            if (e.target.closest('.ag-ev')) { return; }
            var rect  = col.getBoundingClientRect();
            var first = parseInt(col.getAttribute('data-first'), 10);
            var span  = parseInt(col.getAttribute('data-span'), 10);
            var mins  = Math.floor(((e.clientY - rect.top) / rect.height) * span * 2) * 30;  // cada media hora
            var h = first + Math.floor(mins / 60);
            var m = mins % 60;
            if (form.querySelector('[name="apid"]')) {
                // Editando otra cita: abrir una nueva en ese horario
                var u = new URL(location.href);
                u.searchParams.delete('cita');
                u.searchParams.set('fecha', col.getAttribute('data-day'));
                u.searchParams.set('hora', (h < 10 ? '0' : '') + h + ':' + (m ? '30' : '00'));
                u.hash = 'ag-panel';
                location.href = u.toString();
                return;
            }
            dateIn.value = col.getAttribute('data-day');
            timeIn.value = (h < 10 ? '0' : '') + h + ':' + (m ? '30' : '00');
            document.querySelectorAll('.ag-week__col.is-picked').forEach(function (c) { c.classList.remove('is-picked'); });
            col.classList.add('is-picked');
            check();
            var name = form.querySelector('[name="client_name"]');
            if (name && !name.value) { name.focus({ preventScroll: true }); }
            if (window.innerWidth < 992) { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // ---- Aviso de choque de horario ----
    var warn  = form.querySelector('[data-warn]');
    var text  = form.querySelector('[data-warn-text]');
    var timer = null;
    var seq   = 0;
    function check() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var params = new URLSearchParams();
            ['pid', 'date', 'time', 'duration', 'shower_uid', 'apid'].forEach(function (n) {
                var el = form.querySelector('[name="' + n + '"]');
                if (el && el.value) { params.set(n, el.value); }
            });
            if (!params.get('pid') || !params.get('date') || !params.get('time') || !window.fetch) { return; }
            var mine = ++seq;
            fetch(form.getAttribute('data-check') + '?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (mine !== seq || !data || !data.ok) { return; }
                    text.innerHTML = '';
                    data.messages.forEach(function (m) {
                        var p = document.createElement('p');
                        p.textContent = m;
                        text.appendChild(p);
                    });
                    if (data.messages.length) {
                        var p = document.createElement('p');
                        p.className = 'ag-warn__hint';
                        p.textContent = 'Revisa el horario. Si de todos modos quieres guardarla, se te pedirá confirmar.';
                        text.appendChild(p);
                    }
                    warn.hidden = !data.messages.length;
                })
                .catch(function () { /* sin aviso: el servidor vuelve a revisar al guardar */ });
        }, 300);
    }
    form.querySelectorAll('[data-when]').forEach(function (el) {
        el.addEventListener('change', check);
    });
})();
