<?php
/**
 * Elegir qué parte del logo se ve en el círculo (insignia del portal,
 * lista del admin). JS: public/js/logo-cropper.js · Guarda: Agency::saveIcon
 *
 * Variables:
 *   $input  id del <input type="file"> del logo (p. ej. 'fu-logo')
 *   $src    URL del logo actual ('' si no hay)
 *   $bg     color de fondo del círculo (color principal de la inmobiliaria)
 *   $name   nombre corto para la vista previa de la insignia
 */
$bg = $bg ?? '#0A2342';
?>
<div class="lc" data-logo-cropper data-input="<?= esc($input ?? 'fu-logo', 'attr') ?>" data-src="<?= esc($src ?? '', 'attr') ?>" style="--lc-bg: <?= esc($bg, 'attr') ?>;">
    <div class="lc__label">Parte del logo que se ve en el círculo</div>
    <div class="lc__body">
        <div class="lc__view" data-lc-view aria-label="Arrastra el logo para acomodarlo dentro del círculo">
            <img class="lc__img" data-lc-img alt="" draggable="false">
            <span class="lc__empty" data-lc-empty>Elige un logo para acomodarlo aquí</span>
        </div>
        <div class="lc__side">
            <label class="lc__zoom"><span>Alejar</span><input type="range" min="0.5" max="6" step="0.01" value="1" data-lc-zoom aria-label="Acercar o alejar"><span>Acercar</span></label>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-lc-center>Centrar</button>
            <div class="lc__help">Arrastra la imagen para moverla; usa el control (o la rueda del mouse) para acercarla.</div>
            <div class="lc__preview">
                <span class="lc__chip"><canvas width="48" height="48" data-lc-mini></canvas><?= esc($name ?? 'Inmobiliaria') ?></span>
                <canvas class="lc__big" width="92" height="92" data-lc-mini></canvas>
            </div>
            <div class="lc__help">Así se verá en el portal. En las fichas técnicas se usa el logo completo.</div>
        </div>
    </div>
    <input type="hidden" name="logo_icon" data-lc-out>
</div>
<style>
.lc { margin-top: 12px; padding: 14px; border: 1px solid #E5E7EB; border-radius: 12px; background: #FAFAF8; }
.lc__label { font-weight: 600; font-size: 14px; margin-bottom: 10px; }
.lc__body { display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap; }
.lc__view { position: relative; width: 200px; height: 200px; flex: none; border-radius: 50%; overflow: hidden; background: var(--lc-bg); cursor: grab; touch-action: none; box-shadow: 0 0 0 4px #fff, 0 0 0 5px #D1D5DB; }
.lc__view.is-dragging { cursor: grabbing; }
.lc__img { position: absolute; left: 0; top: 0; max-width: none !important; user-select: none; pointer-events: none; transform-origin: 0 0; }
.lc__empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 30px; text-align: center; color: rgba(255,255,255,.75); font-size: 13px; }
.lc:not(.is-ready) .lc__side { opacity: .5; pointer-events: none; }
.lc__side { flex: 1; min-width: 200px; }
.lc__zoom { display: flex; align-items: center; gap: 8px; margin: 0 0 10px; font-size: 12px; color: #6B7280; }
.lc__zoom input { flex: 1; }
.lc__help { margin: 8px 0; font-size: 12px; color: #6B7280; }
.lc__preview { display: flex; align-items: center; gap: 16px; margin-top: 12px; }
.lc__chip { display: inline-flex; align-items: center; gap: 7px; padding: 4px 11px 4px 4px; border-radius: 20px; background: #fff; box-shadow: 0 4px 12px -6px rgba(15,23,42,.4); font-size: 12px; font-weight: 800; }
.lc__chip canvas { width: 24px; height: 24px; border-radius: 50%; background: var(--lc-bg); }
.lc__big { width: 46px; height: 46px; border-radius: 50%; background: var(--lc-bg); }
</style>
