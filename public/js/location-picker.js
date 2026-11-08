/* ================================================================
   "Ubicación en el mapa" del formulario de propiedades
   ([data-location-picker], vista: app/Views/partials/location_picker.php)
   - Clic en el mapa o arrastrar el marcador: guarda lat/lng.
   - "Buscar" y "Usar la dirección del formulario": GET /geocode?q=
     (Geocode::search, que consulta Nominatim con caché).
   - "Estoy en la propiedad": ubicación del dispositivo.
   Necesita Leaflet (lib/leaflet/leaflet.js). Sin jQuery.
   ================================================================ */
(function () {
    'use strict';

    var GOLD = '#C9A96E';
    var MEXICO = [23.6, -102.5];

    function init(box) {
        var mapEl   = box.querySelector('[data-lp-map]');
        var latIn   = box.querySelector('[data-lp-lat]');
        var lngIn   = box.querySelector('[data-lp-lng]');
        var status  = box.querySelector('[data-lp-status]');
        var clear   = box.querySelector('[data-lp-clear]');
        var exact   = box.querySelector('[data-lp-exact]');
        var query   = box.querySelector('[data-lp-query]');
        var form    = box.closest('form');
        var marker  = null;

        if (!mapEl || typeof L === 'undefined') { return; }

        var map = L.map(mapEl, { scrollWheelZoom: false });
        map.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>');
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
        }).addTo(map);
        map.on('focus click', function () { map.scrollWheelZoom.enable(); });
        map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

        var icon = L.divIcon({
            className: 'lp-marker',
            iconSize: [40, 52],
            iconAnchor: [20, 50],
            html: '<svg viewBox="0 0 40 52" width="40" height="52" aria-hidden="true">'
                + '<path d="M20 50.5S38 31 38 19A18 18 0 0 0 2 19c0 12 18 31.5 18 31.5z" fill="' + GOLD + '" stroke="#fff" stroke-width="3"/>'
                + '<path d="M12 22l8-7 8 7v8h-5v-5h-6v5h-5z" fill="#fff"/></svg>'
        });

        function setStatus(text, kind) {
            status.textContent = text;
            status.className = 'lp-status' + (kind ? ' lp-status--' + kind : '');
        }

        function describe() {
            if (!marker) {
                setStatus('Sin punto marcado: se usará la dirección escrita.');
                return;
            }
            var p = marker.getLatLng();
            setStatus('Punto marcado: ' + p.lat.toFixed(6) + ', ' + p.lng.toFixed(6), 'ok');
        }

        function setPoint(latlng, zoom) {
            var lat = Math.round(latlng.lat * 1e6) / 1e6;
            var lng = Math.round(latlng.lng * 1e6) / 1e6;
            if (!marker) {
                marker = L.marker([lat, lng], { draggable: true, icon: icon, autoPan: true, keyboard: true, title: 'Ubicación de la propiedad' }).addTo(map);
                marker.on('dragend', function () { setPoint(marker.getLatLng()); });
            } else {
                marker.setLatLng([lat, lng]);
            }
            latIn.value = lat;
            lngIn.value = lng;
            exact.disabled = false;
            clear.hidden = false;
            if (zoom) { map.setView([lat, lng], Math.max(map.getZoom(), zoom)); }
            describe();
        }

        function clearPoint() {
            if (marker) { map.removeLayer(marker); marker = null; }
            latIn.value = '';
            lngIn.value = '';
            exact.checked = false;
            exact.disabled = true;
            clear.hidden = true;
            describe();
        }

        // Buscar un texto con /geocode y centrar el mapa (marca el punto si la búsqueda es precisa)
        function search(text, button) {
            text = (text || '').trim();
            if (text.length < 3) {
                setStatus('Escribe una dirección más completa.', 'error');
                return;
            }
            if (button) { button.disabled = true; }
            setStatus('Buscando…');
            var url = box.getAttribute('data-geocode-url') + '?q=' + encodeURIComponent(text);
            fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Demasiadas búsquedas seguidas. Espera un minuto.' }; }); })
                .then(function (data) {
                    if (!data.ok) {
                        setStatus(data.error || 'No encontramos esa dirección.', 'error');
                        return;
                    }
                    if (data.bbox) {
                        map.fitBounds([[data.bbox[0], data.bbox[2]], [data.bbox[1], data.bbox[3]]], { maxZoom: 18 });
                    } else {
                        map.setView([data.lat, data.lng], 17);
                    }
                    setPoint({ lat: data.lat, lng: data.lng });
                    setStatus('Encontrado. Revisa que el marcador esté sobre la propiedad y arrástralo si hace falta.', 'ok');
                })
                .catch(function () { setStatus('No se pudo buscar. Revisa tu conexión.', 'error'); })
                .then(function () { if (button) { button.disabled = false; } });
        }

        function formAddress() {
            if (!form) { return ''; }
            var parts = ['loc', 'city', 'state'].map(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                return field ? String(field.value || '').trim() : '';
            }).filter(Boolean);
            return parts.join(', ');
        }

        // Vista inicial
        if (latIn.value && lngIn.value) {
            map.setView([parseFloat(latIn.value), parseFloat(lngIn.value)], 17);
            setPoint({ lat: parseFloat(latIn.value), lng: parseFloat(lngIn.value) });
        } else {
            map.setView(MEXICO, 5);
            describe();
            // Centrar (sin marcar) en la dirección que ya tiene la propiedad
            var address = box.getAttribute('data-address');
            if (address) {
                fetch(box.getAttribute('data-geocode-url') + '?q=' + encodeURIComponent(address), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { if (data.ok && !marker) { map.setView([data.lat, data.lng], 15); } })
                    .catch(function () {});
            }
        }

        map.on('click', function (e) { setPoint(e.latlng); });

        box.querySelector('[data-lp-search]').addEventListener('click', function () { search(query.value, this); });
        query.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); search(query.value, box.querySelector('[data-lp-search]')); } // no enviar el formulario
        });
        box.querySelector('[data-lp-from-form]').addEventListener('click', function () {
            var text = formAddress();
            if (!text) { setStatus('Primero escribe la ubicación y elige la ciudad.', 'error'); return; }
            query.value = text;
            search(text, this);
        });

        var gps = box.querySelector('[data-lp-gps]');
        if (!navigator.geolocation) {
            gps.hidden = true;
        } else {
            gps.addEventListener('click', function () {
                setStatus('Obteniendo tu ubicación…');
                navigator.geolocation.getCurrentPosition(function (pos) {
                    setPoint({ lat: pos.coords.latitude, lng: pos.coords.longitude }, 18);
                    setStatus('Usamos la ubicación de tu dispositivo (precisión ~' + Math.round(pos.coords.accuracy) + ' m). Ajusta el marcador si hace falta.', 'ok');
                }, function () {
                    setStatus('No se pudo obtener tu ubicación. Revisa el permiso de ubicación del navegador.', 'error');
                }, { enableHighAccuracy: true, timeout: 15000 });
            });
        }

        clear.addEventListener('click', clearPoint);

        // El mapa puede estar en una pestaña o columna que cambia de tamaño
        window.addEventListener('resize', function () { map.invalidateSize(); });
        setTimeout(function () { map.invalidateSize(); }, 300);
    }

    function start() {
        document.querySelectorAll('[data-location-picker]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
