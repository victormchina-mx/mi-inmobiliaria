<?php
/**
 * "Ubicación en el mapa" del formulario de propiedades (agente y admin):
 * el usuario busca la dirección o hace clic en el mapa, arrastra el
 * marcador, y elige si los visitantes ven el punto exacto o una zona.
 *
 * Uso (dentro del <form>, en una fila .row):
 *   <?= view('partials/location_picker', ['property' => $property ?? []]) ?>
 * y en la página: lib/leaflet/leaflet.css, css/location-picker.css,
 * lib/leaflet/leaflet.js y js/location-picker.js (ver los formularios).
 *
 * Guarda: App\Libraries\PropertyLocation::fromRequest() (campos lat, lng,
 * exact_location). No se muestra si la base de datos aún no tiene esas
 * columnas (app/Database/sql/2026-11-property-coordinates.sql).
 */
use App\Libraries\Geocoder;
use App\Libraries\PropertyLocation;

if (! PropertyLocation::ready()) {
    return;
}

$property = $property ?? [];
$point    = PropertyLocation::parse(old('lat') ?? ($property['lat'] ?? null), old('lng') ?? ($property['lng'] ?? null));
$exact    = old('exact_location') !== null ? old('exact_location') === '1' : ! empty($property['exact_location']);
$address  = Geocoder::address($property);
$svg      = static fn (string $d): string => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
?>
<div class="col-md-12">
    <div class="lp" data-location-picker
         data-geocode-url="<?= site_url('geocode') ?>"
         data-address="<?= esc($address, 'attr') ?>">
        <div class="lp-head">
            <span class="lp-head__ic" aria-hidden="true"><?= $svg('<path d="M12 21s-7-7.6-7-12a7 7 0 0 1 14 0c0 4.4-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>') ?></span>
            <div>
                <h5 class="lp-title">Ubicación en el mapa</h5>
                <p class="lp-help">Busca la dirección o haz clic en el mapa para colocar el marcador; arrástralo para ajustarlo. Si no marcas nada, se usará la dirección escrita.</p>
            </div>
        </div>

        <div class="lp-search">
            <input type="search" class="form-control lp-q" data-lp-query placeholder="Calle y número, colonia, municipio" aria-label="Buscar dirección" autocomplete="off">
            <button type="button" class="lp-btn lp-btn--dark" data-lp-search><?= $svg('<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>') ?>Buscar</button>
            <button type="button" class="lp-btn" data-lp-from-form title="Buscar la ubicación, la ciudad y el estado escritos arriba"><?= $svg('<path d="M4 6h16M4 12h10M4 18h7"/>') ?>Usar la dirección del formulario</button>
            <button type="button" class="lp-btn" data-lp-gps title="Usar la ubicación de este dispositivo (útil si estás en la propiedad)"><?= $svg('<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8"/>') ?>Estoy en la propiedad</button>
        </div>

        <div class="lp-map" data-lp-map role="application" aria-label="Mapa para marcar la ubicación de la propiedad"></div>

        <div class="lp-foot">
            <span class="lp-status" data-lp-status aria-live="polite"></span>
            <button type="button" class="lp-link" data-lp-clear<?= $point ? '' : ' hidden' ?>>Quitar el punto</button>
        </div>

        <label class="lp-exact">
            <input type="checkbox" name="exact_location" value="1" data-lp-exact<?= $exact ? ' checked' : '' ?><?= $point ? '' : ' disabled' ?>>
            <span>
                <b>Mostrar la ubicación exacta a los visitantes</b>
                <small>Si no lo marcas, verán una zona aproximada de unos <?= PropertyLocation::APPROX_RADIUS ?> m, sin señalar la propiedad.</small>
            </span>
        </label>

        <input type="hidden" name="lat" value="<?= $point ? esc((string) $point['lat'], 'attr') : '' ?>" data-lp-lat>
        <input type="hidden" name="lng" value="<?= $point ? esc((string) $point['lng'], 'attr') : '' ?>" data-lp-lng>
    </div>
</div>
