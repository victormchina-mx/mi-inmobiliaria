/* ================================================================
   Ficha técnica (app/Views/agent/ficha.php)
   - Código QR con el enlace corto (qrcode-generator).
   - Cambio de plantilla sin recargar y vista previa a escala.
   - Descargar imagen PNG (html-to-image) e imprimir / guardar PDF.
   - Plantilla Residencial: "Lo que tiene cerca" con los datos de
     /property/{id}/cerca (los mismos del detalle de la propiedad).
   ================================================================ */
(function () {
    'use strict';

    var page = document.querySelector('[data-ficha]');
    if (!page) { return; }

    var preview = page.querySelector('[data-preview]');
    var stages  = Array.prototype.slice.call(page.querySelectorAll('[data-stage]'));
    var buttons = Array.prototype.slice.call(page.querySelectorAll('[data-tpl]'));
    var status  = page.querySelector('[data-status]');
    var code    = page.getAttribute('data-code');
    var url     = page.getAttribute('data-url');

    // Tamaño de impresión de cada plantilla (pulgadas a 96 px/in)
    var PRINT = {
        elegante:    { page: '8.5in 11in',    width: 816 },
        moderna:     { page: '8in 8in',       width: 768 },
        residencial: { page: '11in 7.333in',  width: 1056 }
    };

    function say(text, error) {
        status.textContent = text || '';
        status.classList.toggle('is-error', !!error);
    }

    function active() {
        return stages.filter(function (s) { return s.classList.contains('is-active'); })[0];
    }

    // ---- Código QR ----
    if (window.qrcode) {
        var qr = window.qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        var svg = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true, alt: 'Código QR de la ficha' });
        page.querySelectorAll('[data-qr]').forEach(function (el) { el.innerHTML = svg; });
    }

    // ---- Escala de la vista previa ----
    function fit() {
        var stage = active();
        if (!stage) { return; }
        var node  = stage.querySelector('[data-ficha-node]');
        var w     = parseInt(node.getAttribute('data-w'), 10);
        var h     = parseInt(node.getAttribute('data-h'), 10);
        var room  = preview.clientWidth - parseFloat(getComputedStyle(preview).paddingLeft) * 2;
        var scale = Math.min(1, room / w);
        stage.style.width = w + 'px';
        stage.style.transform = 'scale(' + scale + ')';
        stage.style.marginBottom = (-(h * (1 - scale))) + 'px';
        stage.style.marginRight = (-(w * (1 - scale))) + 'px';
    }
    window.addEventListener('resize', fit);

    // ---- Cambiar de plantilla ----
    function choose(tpl) {
        buttons.forEach(function (b) {
            var on = b.getAttribute('data-tpl') === tpl;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        stages.forEach(function (s) { s.classList.toggle('is-active', s.getAttribute('data-stage') === tpl); });
        // Conservar la plantilla en la dirección y en "Asesor en la ficha"
        var params = new URLSearchParams(location.search);
        params.set('tpl', tpl);
        history.replaceState(null, '', location.pathname + '?' + params.toString());
        page.querySelectorAll('[data-keep-tpl]').forEach(function (a) {
            var u = new URL(a.href);
            u.searchParams.set('tpl', tpl);
            a.href = u.toString();
        });
        fit();
        say('');
    }
    buttons.forEach(function (b) {
        b.addEventListener('click', function () { choose(b.getAttribute('data-tpl')); });
    });

    // ---- Descargar imagen PNG ----
    var download = page.querySelector('[data-download]');
    download.addEventListener('click', function () {
        if (!window.htmlToImage) { say('No se pudo cargar el generador de imágenes. Recarga la página.', true); return; }
        var stage = active();
        var tpl   = stage.getAttribute('data-stage');
        var node  = stage.querySelector('[data-ficha-node]');
        download.disabled = true;
        say('Generando la imagen…');
        (document.fonts ? document.fonts.ready : Promise.resolve()).then(function () {
            return window.htmlToImage.toPng(node, {
                pixelRatio: tpl === 'elegante' ? 2 : 1,   // la vertical se imprime: más resolución
                cacheBust: false
                // sin backgroundColor: reemplazaría el fondo propio de la plantilla
            });
        }).then(function (dataUrl) {
            var a = document.createElement('a');
            a.href = dataUrl;
            a.download = code + '-' + tpl + '.png';
            document.body.appendChild(a);
            a.click();
            a.remove();
            say('Imagen descargada: ' + a.download);
        }).catch(function () {
            say('No se pudo generar la imagen. Intenta de nuevo o usa "Imprimir o guardar PDF".', true);
        }).then(function () { download.disabled = false; });
    });

    // ---- Imprimir o guardar PDF ----
    var printStyle = document.createElement('style');
    document.head.appendChild(printStyle);
    page.querySelector('[data-print]').addEventListener('click', function () {
        var stage = active();
        var tpl   = stage.getAttribute('data-stage');
        var node  = stage.querySelector('[data-ficha-node]');
        var conf  = PRINT[tpl];
        var scale = conf.width / parseInt(node.getAttribute('data-w'), 10);
        printStyle.textContent = '@page { size: ' + conf.page + '; margin: 0; }'
            + '@media print { body.ft-printing .ft-stage.is-active { transform: scale(' + scale + ') !important; margin: 0 !important; } }';
        document.body.classList.add('ft-printing');
        window.print();
    });
    window.addEventListener('afterprint', function () {
        document.body.classList.remove('ft-printing');
        fit();
    });

    // ---- Copiar enlace ----
    var copy = page.querySelector('[data-copy]');
    if (copy) {
        copy.addEventListener('click', function () {
            var text = copy.getAttribute('data-copy');
            (navigator.clipboard && window.isSecureContext
                ? navigator.clipboard.writeText(text)
                : Promise.reject()
            ).then(function () { say('Enlace copiado: ' + text); }, function () { window.prompt('Copia el enlace:', text); });
        });
    }

    // ---- Residencial: lo que tiene cerca ----
    var nearUrl = page.getAttribute('data-nearby');
    var near    = page.querySelector('[data-near-items]');
    if (nearUrl && near && window.fetch) {
        fetch(nearUrl, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.ok || !data.places || !data.places.length) { return; }
                var order  = ['school', 'park', 'health', 'shop', 'transit', 'culture', 'food'];
                var counts = {};
                data.places.forEach(function (p) { counts[p.cat] = (counts[p.cat] || 0) + 1; });
                var cats = order.filter(function (c) { return counts[c] && data.categories[c]; }).slice(0, 5);
                if (!cats.length) { return; }
                near.innerHTML = '';
                cats.forEach(function (c) {
                    var item = document.createElement('div');
                    var icon = document.createElement('i');
                    icon.className = 'ms';
                    icon.textContent = data.categories[c].icon;
                    var n = document.createElement('b');
                    n.textContent = counts[c];
                    item.appendChild(icon);
                    item.appendChild(n);
                    item.appendChild(document.createTextNode(data.categories[c].label));
                    near.appendChild(item);
                });
                // Zona: agregar el transporte más cercano
                var transit = data.places.filter(function (p) { return p.cat === 'transit'; })[0];
                var zone = page.querySelector('[data-zone-text] span');
                if (transit && zone) {
                    zone.textContent += ' · ' + transit.name + (transit.dist < 50 ? ' a unos pasos' : ' a ' + transit.min + ' min a pie');
                }
            })
            .catch(function () { /* se queda el texto general */ });
    }

    fit();
    if (document.fonts) { document.fonts.ready.then(fit); }
})();
