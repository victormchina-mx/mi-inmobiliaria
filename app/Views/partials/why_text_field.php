<?php
/**
 * Campo "¿Por qué vivir aquí?" de los formularios de propiedad (agente y admin).
 * Texto propio del agente; si queda vacío, el sitio escribe uno automático con
 * lo que hay cerca (App\Libraries\WhyText, NearbyPlaces::summary).
 *
 * Uso (dentro de una fila .row del formulario):
 *   <?= view('partials/why_text_field', ['property' => $property ?? []]) ?>
 * No se muestra si la base de datos aún no tiene la columna why_text.
 */
use App\Libraries\WhyText;

if (! WhyText::ready()) {
    return;
}

$value = old('why_text') ?? WhyText::of($property ?? []);
$max   = WhyText::MAX_LENGTH;
?>
<div class="col-md-12">
    <div class="form-group why-field">
        <label for="why_text">¿Por qué vivir aquí? <span class="text-muted font-weight-normal">(opcional)</span></label>
        <textarea name="why_text" id="why_text" class="form-control" rows="4" maxlength="<?= $max ?>"
                  placeholder="Ej.: Calle tranquila y arbolada, a dos cuadras del parque y a cinco minutos del Metro. Los vecinos se conocen y hay mercado los domingos."><?= esc($value) ?></textarea>
        <div class="d-flex justify-content-between">
            <small class="form-text text-muted">Aparece en el recuadro "¿Por qué vivir aquí?" del detalle. Si lo dejas vacío, se escribe uno automático con lo que hay cerca.</small>
            <small class="form-text text-muted text-nowrap ml-3" data-why-count><?= mb_strlen($value) ?>/<?= $max ?></small>
        </div>
    </div>
</div>
<script>
(function () {
    var field = document.getElementById('why_text');
    var count = document.querySelector('[data-why-count]');
    if (!field || !count) { return; }
    field.addEventListener('input', function () { count.textContent = field.value.length + '/<?= $max ?>'; });
})();
</script>
