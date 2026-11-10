<?php
/**
 * Campos de terreno, estacionamientos, antigüedad y llaves del formulario de
 * propiedad (agente y admin). Van en la fila .row del formulario.
 * No se muestran si la base de datos aún no tiene las columnas
 * (app/Database/sql/2026-11-property-extras.sql).
 */
use App\Libraries\PropertyExtras;

if (! PropertyExtras::ready()) {
    return;
}

$property = $property ?? [];
$val      = static fn (string $f) => old($f) ?? ($property[$f] ?? '');
?>
<div class="col-md-3">
    <div class="form-group">
        <label for="pe-land">Terreno (m²)</label>
        <input type="number" id="pe-land" name="land_size" class="form-control" min="0" max="999999" value="<?= esc($val('land_size')) ?>">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        <label for="pe-parking">Estacionamientos</label>
        <input type="number" id="pe-parking" name="parking" class="form-control" min="0" max="99" value="<?= esc($val('parking')) ?>">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        <label for="pe-age">Antigüedad (años)</label>
        <input type="number" id="pe-age" name="age_years" class="form-control" min="0" max="300" value="<?= esc($val('age_years')) ?>">
        <small class="form-text text-muted">0 = a estrenar</small>
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        <label for="pe-keys">Llaves</label>
        <select id="pe-keys" name="keys_status" class="form-control">
            <option value="">Sin dato</option>
            <?php foreach (PropertyExtras::KEYS as $k => $label): ?>
            <option value="<?= $k ?>"<?= (string) $val('keys_status') === $k ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach ?>
        </select>
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label for="pe-keys-note">Nota sobre las llaves <span class="text-muted font-weight-normal">(solo la ven los agentes)</span></label>
        <input type="text" id="pe-keys-note" name="keys_note" class="form-control" maxlength="120" placeholder="Ej.: Llavero 12 en recepción · Pedir al portero" value="<?= esc($val('keys_note')) ?>">
    </div>
</div>
