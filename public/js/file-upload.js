/* ================================================================
   Campo de imagen con vista previa ([data-file-upload])
   Reemplaza visualmente el input nativo ("Choose File / No file
   chosen") por un botón en español con vista previa de la imagen.
   Si el campo ya tiene una imagen guardada (al editar), la muestra
   y vuelve a ella cuando se cancela o se elige un archivo inválido.
   Vista: app/Views/partials/file_upload.php
   ================================================================ */
(function () {
    'use strict';

    var ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    function init(box) {
        var input   = box.querySelector('input[type="file"]');
        var img     = box.querySelector('.file-upload__preview img');
        var icon    = box.querySelector('.file-upload__preview svg');
        var nameEl  = box.querySelector('[data-file-upload-name]');
        var btnText = box.querySelector('[data-file-upload-btn]');
        if (!input || box.getAttribute('data-file-upload-ready')) { return; }
        box.setAttribute('data-file-upload-ready', '1');

        var initialSrc  = img ? (img.getAttribute('src') || '') : '';
        var initialName = nameEl ? nameEl.textContent : '';
        var initialBtn  = btnText ? btnText.textContent : '';
        var changeText  = box.getAttribute('data-change-text') || 'Cambiar imagen';
        var objectUrl   = null;

        function showImage(src) {
            if (!img) { return; }
            if (src) {
                img.src = src;
                img.hidden = false;
                if (icon) { icon.hidden = true; }
            } else {
                img.hidden = true;
                img.removeAttribute('src');
                if (icon) { icon.hidden = false; }
            }
        }

        function reset(message) {
            if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
            showImage(initialSrc);
            if (btnText) { btnText.textContent = initialBtn; }
            box.classList.remove('is-selected');
            box.classList.toggle('has-error', !!message);
            if (nameEl) { nameEl.textContent = message || initialName; }
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { reset(); return; }

            if (ALLOWED.indexOf(file.type) === -1) {
                input.value = '';
                reset('Formato no permitido. Usa JPG, PNG, GIF o WebP.');
                return;
            }

            reset();
            box.classList.add('is-selected');
            if (nameEl)  { nameEl.textContent = file.name; }
            if (btnText) { btnText.textContent = changeText; }
            if (window.URL && URL.createObjectURL) {
                objectUrl = URL.createObjectURL(file);
                showImage(objectUrl);
            }
        });

        // Al recargar con "atrás", el navegador puede conservar el archivo
        if (input.files && input.files.length) {
            input.dispatchEvent(new Event('change'));
        }
    }

    function initAll() {
        document.querySelectorAll('[data-file-upload]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
