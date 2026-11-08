/* ================================================================
   Página "Ubicación" de una propiedad
   - Mapa Leaflet + OpenStreetMap con marcador dorado y el círculo de
     la zona aproximada (#loc-map, datos en sus atributos data-*).
   - Botones: centrar, pantalla completa, copiar dirección, compartir.
   Vista: app/Views/property/location.php · Estilos: .loc-* en style.css
   ================================================================ */
(function () {
    'use strict';

    var GOLD = '#C9A96E';
    var toastTimer;

    function toast(message) {
        var el = document.querySelector('[data-toast]');
        if (!el) { return; }
        el.textContent = message;
        el.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.classList.remove('is-visible'); }, 2500);
    }

    function copy(text, okMessage) {
        function fallback() {
            var area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(area);
            toast(ok ? okMessage : 'No se pudo copiar');
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { toast(okMessage); }, fallback);
        } else {
            fallback();
        }
    }

    function initButtons() {
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                copy(btn.getAttribute('data-copy'), 'Dirección copiada');
            });
        });

        document.querySelectorAll('[data-share]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var data = { title: btn.getAttribute('data-share-title') || document.title, url: window.location.href };
                if (navigator.share) {
                    navigator.share(data).catch(function () { /* el usuario canceló */ });
                } else {
                    copy(data.url, 'Enlace copiado');
                }
            });
        });
    }

    // Botón de control de Leaflet con un ícono Material Symbols
    function controlButton(container, icon, label, onClick) {
        var btn = L.DomUtil.create('a', 'loc-ctrl-btn', container);
        btn.href = '#';
        btn.setAttribute('role', 'button');
        btn.setAttribute('aria-label', label);
        btn.title = label;
        btn.innerHTML = '<i class="ms" aria-hidden="true">' + icon + '</i>';
        L.DomEvent.on(btn, 'click', function (e) {
            L.DomEvent.preventDefault(e);
            L.DomEvent.stopPropagation(e);
            onClick(btn);
        });
        return btn;
    }

    function initMap() {
        var el = document.getElementById('loc-map');
        if (!el || typeof L === 'undefined') { return; }

        var center = L.latLng(parseFloat(el.dataset.lat), parseFloat(el.dataset.lng));
        var level  = el.dataset.level;
        var bbox   = null;
        try { bbox = JSON.parse(el.dataset.bbox || 'null'); } catch (e) { bbox = null; }

        var map = L.map(el, {
            zoomControl: false,
            scrollWheelZoom: false, // la rueda desplaza la página; se activa al hacer clic en el mapa
            attributionControl: true
        });
        map.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>');

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
        }).addTo(map);

        map.on('click focus', function () { map.scrollWheelZoom.enable(); });
        map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

        // Área a mostrar: un círculo para zona/ciudad; para un estado, su contorno
        var bounds, label = el.dataset.label;
        var area = bbox ? L.latLngBounds([bbox[0], bbox[2]], [bbox[1], bbox[3]]) : null;

        if (level === 'state') {
            bounds = area || center.toBounds(60000);
        } else {
            var radius = area ? map.distance(area.getSouthWest(), area.getNorthEast()) / 2 : 800;
            radius = Math.max(level === 'city' ? 1500 : 450, Math.min(level === 'city' ? 8000 : 2500, radius));
            var circle = L.circle(center, {
                radius: radius, color: GOLD, weight: 2, opacity: 0.9,
                fillColor: GOLD, fillOpacity: 0.16, interactive: false
            }).addTo(map);
            bounds = center.toBounds(radius * 2); // circle.getBounds() requiere que el mapa ya tenga vista

            if (label) {
                // Etiqueta en el borde inferior del círculo
                var text = document.createElement('span');
                text.textContent = label;
                L.marker([center.lat - radius / 111320, center.lng], {
                    interactive: false, keyboard: false,
                    icon: L.divIcon({ className: 'loc-zone-label', html: text, iconSize: null })
                }).addTo(map);
            }
        }

        L.marker(center, {
            keyboard: false,
            title: label || 'Ubicación',
            alt: label || 'Ubicación',
            icon: L.divIcon({
                className: 'loc-marker',
                iconSize: [44, 57],
                iconAnchor: [22, 55],
                html: '<svg viewBox="0 0 40 52" width="44" height="57" aria-hidden="true">'
                    + '<path d="M20 50.5S38 31 38 19A18 18 0 0 0 2 19c0 12 18 31.5 18 31.5z" fill="' + GOLD + '" stroke="#fff" stroke-width="3"/>'
                    + '<path d="M12 22l8-7 8 7v8h-5v-5h-6v5h-5z" fill="#fff"/></svg>'
            })
        }).addTo(map);

        // En escritorio la tarjeta tapa la parte izquierda del mapa
        function fit(animate) {
            var wide = window.matchMedia('(min-width: 768px)').matches;
            map.fitBounds(bounds, {
                paddingTopLeft: wide ? [400, 50] : [30, 30],
                paddingBottomRight: [30, 50],
                maxZoom: 16,
                animate: animate !== false
            });
        }
        fit(false);

        L.control.zoom({ position: 'topright', zoomInTitle: 'Acercar', zoomOutTitle: 'Alejar' }).addTo(map);

        var Tools = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function () {
                var box = L.DomUtil.create('div', 'leaflet-bar loc-tools');
                controlButton(box, 'my_location', 'Centrar el mapa', function () { fit(); });

                var wrap = document.querySelector('[data-loc-wrap]');
                if (wrap && wrap.requestFullscreen && document.fullscreenEnabled) {
                    controlButton(box, 'open_in_full', 'Pantalla completa', function (btn) {
                        if (document.fullscreenElement) { document.exitFullscreen(); }
                        else { wrap.requestFullscreen(); }
                    });
                    document.addEventListener('fullscreenchange', function () {
                        var full = document.fullscreenElement === wrap;
                        var btn = box.querySelectorAll('.loc-ctrl-btn')[1];
                        btn.querySelector('.ms').textContent = full ? 'close_fullscreen' : 'open_in_full';
                        btn.setAttribute('aria-label', full ? 'Salir de pantalla completa' : 'Pantalla completa');
                        btn.title = btn.getAttribute('aria-label');
                        setTimeout(function () { map.invalidateSize(); fit(false); }, 150);
                    });
                }
                L.DomEvent.disableClickPropagation(box);
                return box;
            }
        });
        new Tools().addTo(map);

        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () { map.invalidateSize(); }, 200);
        });
    }

    function init() {
        initButtons();
        initMap();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
