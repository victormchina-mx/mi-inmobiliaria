<?php
/**
 * Superficie de la propiedad en el formulario (agente y admin), juntas:
 * m² construidos y m² de terreno (hasta 2 decimales). Si el tipo es
 * "Terreno" solo se pide el terreno. Van en la fila .row del formulario.
 *
 * Variables: $property (fila o []), $colClass (clase de columna)
 */
use App\Libraries\PropertyExtras;

$property = $property ?? [];
$col      = $colClass ?? 'col-md-3';
$size     = old('asize') ?? ($property['size'] ?? 0);
$land     = old('land_size') ?? ($property['land_size'] ?? '');
if ($land !== '' && $land !== null && is_numeric($land)) {
    $land = rtrim(rtrim(number_format((float) $land, 2, '.', ''), '0'), '.');   // 496.00 → 496
}
?>
<div class="w-100" aria-hidden="true"></div><?php /* nueva fila: construidos y terreno juntos */ ?>
<div class="<?= $col ?>" data-pf-built>
    <div class="form-group">
        <label for="pf-built">m² construidos</label>
        <input type="number" id="pf-built" name="asize" class="form-control" min="0" value="<?= esc((string) $size) ?>">
    </div>
</div>
<?php if (PropertyExtras::ready()): ?>
<div class="<?= $col ?>">
    <div class="form-group">
        <label for="pf-land">m² de terreno</label>
        <input type="number" id="pf-land" name="land_size" class="form-control" min="0" max="999999.99" step="0.01" inputmode="decimal" value="<?= esc((string) $land) ?>" placeholder="Ej.: 496.50">
    </div>
</div>
<?php endif ?>
<script>
// Tipo "Terreno": no hay construcción, solo se pide el terreno
(function () {
    var type = document.querySelector('select[name="ptype"]');
    var built = document.querySelector('[data-pf-built]');
    if (!type || !built) { return; }
    var apply = function () { built.style.display = type.value === 'terreno' ? 'none' : ''; };
    type.addEventListener('change', apply);
    if (window.jQuery) { window.jQuery(type).on('change', apply); }   // select2
    apply();
})();
</script>
