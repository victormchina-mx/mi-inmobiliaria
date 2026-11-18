/* ================================================================
   Agenda de citas (app/Views/agent/agenda.php)
   - Sin recargar la página: moverse entre semanas/meses (< Hoy >), cambiar
     entre Semana y Mes, filtrar por agente, abrir una cita y guardarla o
     eliminarla solo cambian el bloque .ag-layout. La página no se sube al
     inicio y el botón Atrás del navegador sigue funcionando.
     Sin JS (o si algo falla) todo funciona con recarga normal.
   - Clic en un horario libre (semana) o en un día (mes): llena fecha y hora.
   - Sección 1: datos del inmueble elegido (dirección, responsable, llaves).
   - Aviso en vivo si la cita choca con otra (mismo inmueble o agente).
   - Confirmación antes de eliminar.
   ================================================================ */
(function () {
    'use strict';

    var layout = document.querySelector('.ag-layout');
    if (!layout) { return; }

    var canAjax = !!(window.fetch && window.history && window.history.pushState && window.DOMParser && window.FormData && window.URL);
    var desk    = function () { return window.innerWidth >= 992; };

    // ---------------------------------------------------------------
    // Aviso flotante ("Cita agendada.", errores)
    // ---------------------------------------------------------------
    var toast = null;
    var toastTimer = null;
    function showToast(message, isError) {
        if (!message) { return; }
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'ag-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.innerHTML = '<i class="ms" aria-hidden="true"></i><span></span>';
            document.body.appendChild(toast);
        }
        toast.classList.toggle('is-error', !!isError);
        toast.querySelector('.ms').textContent = isError ? 'warning' : 'event_available';
        toast.querySelector('span').textContent = message;
        // reinicia la animación
        toast.classList.remove('is-shown');
        void toast.offsetWidth;
        toast.classList.add('is-shown');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('is-shown'); }, isError ? 6000 : 3200);
    }

    // ---------------------------------------------------------------
    // Navegación sin recarga
    // ---------------------------------------------------------------
    var agendaPath = new URL(document.querySelector('[data-agenda-filter]').getAttribute('action'), location.href).pathname;
    var busy = 0;

    function isAgendaUrl(u) {
        return u.origin === location.origin && u.pathname === agendaPath;
    }

    function scrollToEl(el) {
        if (!el) { return; }
        var header = document.querySelector('.header-sticky.fixed-top, .fixed-top');
        var offset = (header ? header.offsetHeight : 70) + 12;
        window.scrollTo({ top: el.getBoundingClientRect().top + window.pageYOffset - offset, behavior: 'smooth' });
    }

    // Reemplaza el calendario y el panel con los de la respuesta
    function swap(html, url, opts) {
        var doc   = new DOMParser().parseFromString(html, 'text/html');
        var fresh = doc.querySelector('.ag-layout');
        if (!fresh) { location.href = url; return; }   // p. ej. la sesión expiró y mandó al login

        layout.innerHTML = fresh.innerHTML;
        if (doc.title) { document.title = doc.title; }

        // Los avisos de la recarga anterior ya no aplican
        document.querySelectorAll('.alert-success, .alert-danger').forEach(function (a) {
            var box = a.closest('.container.mt-2');
            (box || a).remove();
        });
        var ok  = doc.querySelector('.alert-success');
        var bad = doc.querySelector('.alert-danger');
        if (bad) { showToast(bad.textContent.trim(), true); } else if (ok) { showToast(ok.textContent.trim(), false); }

        if (opts.push) {
            history.pushState({ agenda: true }, '', url);
        } else if (opts.replace) {
            history.replaceState({ agenda: true }, '', url);
        }
        bind();

        if (opts.panel && !desk()) {
            scrollToEl(document.getElementById('ag-panel'));
        }
    }

    function go(url, opts) {
        opts = opts || {};
        if (!canAjax) { location.href = url; return; }
        var mine = ++busy;
        layout.classList.add('is-loading');
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' } })
            .then(function (r) { return r.text().then(function (t) { return { url: r.url || url, html: t }; }); })
            .then(function (res) {
                if (mine !== busy) { return; }
                var finalUrl = new URL(res.url, location.href);
                var asked    = new URL(url, location.href);
                finalUrl.hash = asked.hash;
                swap(res.html, finalUrl.toString(), opts);
            })
            .catch(function () { location.href = url; })
            .then(function () { if (mine === busy) { layout.classList.remove('is-loading'); } });
    }

    function send(form, submitter) {
        var data = new FormData(form);
        if (submitter && submitter.name) { data.append(submitter.name, submitter.value); }
        var mine = ++busy;
        layout.classList.add('is-loading');
        fetch(form.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'text/html' } })
            .then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.text().then(function (t) { return { url: r.url, html: t }; });
            })
            .then(function (res) {
                if (mine !== busy) { return; }
                swap(res.html, res.url || location.href, { push: res.url && res.url !== location.href });
            })
            .catch(function () {
                // Como antes: envío normal con recarga
                if (submitter && submitter.name) {
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = submitter.name; h.value = submitter.value;
                    form.appendChild(h);
                }
                HTMLFormElement.prototype.submit.call(form);
            })
            .then(function () { if (mine === busy) { layout.classList.remove('is-loading'); } });
    }

    if (canAjax) {
        history.replaceState({ agenda: true }, '', location.href);

        // Enlaces del calendario y del panel
        layout.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
            var a = e.target.closest('a[href]');
            if (!a || a.target) { return; }
            var u = new URL(a.href, location.href);
            if (!isAgendaUrl(u)) { return; }
            var here = new URL(location.href);
            if (u.search === here.search && u.hash) { return; }   // ancla en la misma vista
            e.preventDefault();
            go(u.toString(), { push: true, panel: u.hash === '#ag-panel' || u.searchParams.has('cita') });
        });

        // Guardar / eliminar
        layout.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
                return;
            }
            if (form.method.toLowerCase() !== 'post') { return; }
            e.preventDefault();
            send(form, e.submitter || null);
        });

        // Atrás / Adelante del navegador
        window.addEventListener('popstate', function () {
            go(location.href, {});
        });
    } else {
        layout.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    }

    // ---------------------------------------------------------------
    // Lo que se vuelve a conectar cada vez que cambia el contenido
    // ---------------------------------------------------------------
    function bind() {
        // Filtro de agente: se aplica al elegirlo
        var filter = layout.querySelector('[data-agenda-filter]');
        if (filter) {
            var sel = filter.querySelector('select');
            sel.addEventListener('change', function () {
                if (!canAjax) { filter.submit(); return; }
                var u = new URL(filter.getAttribute('action'), location.href);
                u.search = new URLSearchParams(new FormData(filter)).toString();
                go(u.toString(), { push: true });
            });
            filter.addEventListener('submit', function (e) { e.preventDefault(); sel.dispatchEvent(new Event('change')); });
        }

        var form = layout.querySelector('[data-agenda-form]');

        // Abrir una cita nueva en otra fecha/hora (cuando el panel muestra una cita)
        function openNew(date, time) {
            var u = new URL(location.href);
            u.searchParams.delete('cita');
            u.searchParams.set('fecha', date);
            if (time) { u.searchParams.set('hora', time); } else { u.searchParams.delete('hora'); }
            u.hash = 'ag-panel';
            go(u.toString(), { push: true, panel: true });
        }

        // Llenar el formulario con la fecha/hora elegida
        function pick(cell, date, time) {
            if (!form || form.querySelector('[name="apid"]') || !layout.querySelector('[data-agenda-form]')) {
                openNew(date, time);
                return;
            }
            form.querySelector('[name="date"]').value = date;
            if (time) { form.querySelector('[name="time"]').value = time; }
            layout.querySelectorAll('.is-picked').forEach(function (c) { c.classList.remove('is-picked'); });
            cell.classList.add('is-picked');
            check();
            var name = form.querySelector('[name="client_name"]');
            if (name && !name.value) { name.focus({ preventScroll: true }); }
            if (!desk()) { scrollToEl(form); }
        }

        // Semana: clic en un horario libre
        layout.querySelectorAll('[data-day]').forEach(function (col) {
            col.addEventListener('click', function (e) {
                if (e.target.closest('.ag-ev')) { return; }
                var rect  = col.getBoundingClientRect();
                var first = parseInt(col.getAttribute('data-first'), 10);
                var span  = parseInt(col.getAttribute('data-span'), 10);
                var mins  = Math.floor(((e.clientY - rect.top) / rect.height) * span * 2) * 30;  // cada media hora
                var h = first + Math.floor(mins / 60);
                var m = mins % 60;
                pick(col, col.getAttribute('data-day'), (h < 10 ? '0' : '') + h + ':' + (m ? '30' : '00'));
            });
        });

        // Mes: clic en un día
        layout.querySelectorAll('[data-date]').forEach(function (cell) {
            cell.addEventListener('click', function (e) {
                if (!desk()) {
                    // Celular: si el día tiene citas, ir a ellas en la lista
                    var list = document.getElementById('ag-d-' + cell.getAttribute('data-date'));
                    if (list) { e.preventDefault(); scrollToEl(list); return; }
                } else if (e.target.closest('a')) {
                    return;
                }
                e.preventDefault();
                pick(cell, cell.getAttribute('data-date'), null);
            });
        });

        if (!form) { return; }

        var props = {};
        try { props = JSON.parse(form.querySelector('[data-properties]').textContent) || {}; } catch (err) { props = {}; }

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
                        if (mine !== seq || !data || !data.ok || !form.isConnected) { return; }
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
    }

    bind();
})();
